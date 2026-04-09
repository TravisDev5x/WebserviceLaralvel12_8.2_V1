<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\FailedWebhook;
use App\Models\WebhookLog;
use App\Services\Bitrix24ConnectorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBotmakerPayload implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 300, 900, 3600];

    public int $timeout = 30;

    public function __construct(public int $webhookLogId)
    {
        $this->onQueue('webhooks');
        $this->tries = (int) config_dynamic('retry.max_attempts', 5);
        $backoff = config_dynamic('retry.backoff_schedule', [30, 60, 300, 900, 3600]);
        $this->backoff = is_array($backoff) && $backoff !== [] ? array_values(array_map('intval', $backoff)) : [30, 60, 300, 900, 3600];
        $this->timeout = (int) config_dynamic('retry.http_timeout', 30);
    }

    // =========================================================================
    //  v2 — imconnector: Inject message into Bitrix24 Open Channel
    // =========================================================================

    public function handle(
        Bitrix24ConnectorService $connectorService,
    ): void {
        $webhookLog = WebhookLog::query()->findOrFail($this->webhookLogId);
        $startedAt = microtime(true);

        $this->markAsProcessing($webhookLog);

        $payload = $this->normalizePayload($webhookLog->payload_in);

        // T5.1: Deduplication via cache lock using Botmaker message ID
        $messageId = (string) ($payload['_id'] ?? $payload['messageId'] ?? $payload['id'] ?? '');
        if ($messageId !== '') {
            $lockKey = "botmaker_msg_{$messageId}";
            $lock = Cache::lock($lockKey, 60);

            if (! $lock->get()) {
                Log::channel('webhook')->info('Duplicate Botmaker message discarded', [
                    'message_id' => $messageId,
                    'webhook_log_id' => $this->webhookLogId,
                ]);

                $webhookLog->update([
                    'status' => WebhookLog::STATUS_SENT,
                    'response_body' => 'Duplicate message — skipped',
                    'processing_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ]);

                return;
            }
        }

        try {
            $phone = (string) ($payload['contactId'] ?? $payload['whatsappNumber'] ?? $payload['phone'] ?? '');
            $firstName = (string) ($payload['firstName'] ?? '');
            $lastName = (string) ($payload['lastName'] ?? '');
            $clientName = trim("{$firstName} {$lastName}");
            if ($clientName === '') {
                $clientName = $phone;
            }

            $messageText = (string) data_get($payload, 'messages.0.message', data_get($payload, 'message', ''));

            $response = $connectorService->sendSingleMessage($phone, $clientName, $messageText);

            $this->finalizeFromResponse($webhookLog, $response, $startedAt);

            if (! $response['success']) {
                throw new \RuntimeException($response['body']);
            }
        } catch (Throwable $exception) {
            $this->markAsFailed($webhookLog, $exception, $startedAt);
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $webhookLog = WebhookLog::query()->find($this->webhookLogId);
        if (! $webhookLog instanceof WebhookLog) {
            return;
        }

        $payload = $this->normalizePayload($webhookLog->payload_in);
        $targetUrl = 'imconnector.send.messages';

        FailedWebhook::createFromLog(
            $webhookLog,
            $payload,
            $targetUrl,
            $exception->getMessage(),
            (int) ($webhookLog->http_status ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function markAsProcessing(WebhookLog $webhookLog): void
    {
        if (method_exists($webhookLog, 'markAsProcessing')) {
            $webhookLog->markAsProcessing();

            return;
        }

        $webhookLog->status = 'processing';
        $webhookLog->save();
    }

    /**
     * @param  array{success: bool, http_status: int, body: string}  $response
     */
    private function finalizeFromResponse(WebhookLog $webhookLog, array $response, float $startedAt): void
    {
        $processingMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($response['success']) {
            if (method_exists($webhookLog, 'markAsSent')) {
                $webhookLog->markAsSent(
                    httpStatus: $response['http_status'],
                    responseBody: $response['body'],
                    processingMs: $processingMs,
                );
            }

            $webhookLog->status = 'sent';
        } else {
            if (method_exists($webhookLog, 'markAsFailed')) {
                $webhookLog->markAsFailed(
                    errorMessage: (string) $response['body'],
                    errorType: WebhookLog::ERROR_SERVER,
                    httpStatus: $response['http_status'],
                );
            }

            $webhookLog->status = 'failed';
            $webhookLog->error_message = (string) $response['body'];
            $webhookLog->error_type = WebhookLog::ERROR_SERVER;
        }

        $webhookLog->payload_out = $response;
        $webhookLog->http_status = $response['http_status'];
        $webhookLog->response_body = $response['body'];
        $webhookLog->processing_ms = $processingMs;
        $webhookLog->save();
    }

    private function markAsFailed(WebhookLog $webhookLog, Throwable $exception, float $startedAt): void
    {
        $processingMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (method_exists($webhookLog, 'markAsFailed')) {
            $webhookLog->markAsFailed(
                errorMessage: $exception->getMessage(),
                errorType: WebhookLog::ERROR_UNKNOWN,
                httpStatus: (int) ($webhookLog->http_status ?? 0),
            );
        }

        $webhookLog->status = 'failed';
        $webhookLog->error_message = $exception->getMessage();
        $webhookLog->error_type = WebhookLog::ERROR_UNKNOWN;
        $webhookLog->processing_ms = $processingMs;
        $webhookLog->save();
    }
}
