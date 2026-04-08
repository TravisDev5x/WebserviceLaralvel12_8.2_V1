<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuthorizedToken;
use App\Models\FailedWebhook;
use App\Models\FieldMapping;
use App\Models\NotificationRule;
use App\Models\WebhookLog;
use App\Services\Bitrix24Service;
use App\Services\BotmakerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessBitrix24Payload implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 300, 900, 3600];

    public int $timeout = 15;

    public function __construct(public WebhookLog $webhookLog)
    {
        $this->onQueue('webhooks');
        $this->tries = (int) config_dynamic('retry.max_attempts', 5);
        $backoff = config_dynamic('retry.backoff_schedule', [30, 60, 300, 900, 3600]);
        $this->backoff = is_array($backoff) && $backoff !== [] ? array_values(array_map('intval', $backoff)) : [30, 60, 300, 900, 3600];
        $this->timeout = (int) config_dynamic('retry.http_timeout', 15);
    }

    public function handle(
        BotmakerService $botmakerService,
        Bitrix24Service $bitrix24Service,
    ): void {
        $startedAt = microtime(true);

        $this->markAsProcessing();

        $payload = $this->normalizePayload($this->webhookLog->payload_in);
        $leadId = (int) ($payload['data']['FIELDS']['ID'] ?? 0);
        $lead = $leadId > 0 ? $bitrix24Service->getLeadById($leadId) : null;
        $payloadForParsing = is_array($lead) ? [
            ...$payload,
            'data' => [
                ...((array) ($payload['data'] ?? [])),
                'FIELDS' => $lead,
            ],
        ] : $payload;

        $parsed = $bitrix24Service->parseIncomingPayload($payloadForParsing);
        $parsed = array_merge($parsed, $this->applyDynamicMappings($payload));

        try {
            // TODO: Reemplazar esta regla por lógica real cuando se definan eventos de negocio.
            $shouldNotify = (bool) ($parsed['should_notify'] ?? true);

            if (! $shouldNotify) {
                $this->finalizeFromResponse([
                    'success' => true,
                    'http_status' => 200,
                    'body' => 'Evento ignorado por regla de negocio',
                ], $startedAt);

                return;
            }

            $phone = (string) ($parsed['phone'] ?? '');
            $message = $this->resolveNotificationMessage($parsed);

            $response = $botmakerService->sendMessage($phone, $message);

            $this->finalizeFromResponse($response, $startedAt);

            if (! $response['success']) {
                throw new \RuntimeException($response['body']);
            }
        } catch (Throwable $exception) {
            $this->markAsFailed($exception, $startedAt);
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $payload = $this->normalizePayload($this->webhookLog->payload_in);
        $targetUrl = rtrim(AuthorizedToken::resolvedBotmakerApiUrl(), '/').'/chats-actions/send-messages';

        FailedWebhook::createFromLog(
            $this->webhookLog,
            $payload,
            $targetUrl,
            $exception->getMessage(),
            (int) ($this->webhookLog->http_status ?? 0),
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

    private function markAsProcessing(): void
    {
        if (method_exists($this->webhookLog, 'markAsProcessing')) {
            $this->webhookLog->markAsProcessing();

            return;
        }

        $this->webhookLog->status = 'processing';
        $this->webhookLog->save();
    }

    /**
     * @param  array{success: bool, http_status: int, body: string}  $response
     */
    private function finalizeFromResponse(array $response, float $startedAt): void
    {
        $processingMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($response['success']) {
            if (method_exists($this->webhookLog, 'markAsSent')) {
                $this->webhookLog->markAsSent(
                    httpStatus: $response['http_status'],
                    responseBody: $response['body'],
                    processingMs: $processingMs,
                );
            }

            $this->webhookLog->status = 'sent';
        } else {
            if (method_exists($this->webhookLog, 'markAsFailed')) {
                $this->webhookLog->markAsFailed(
                    errorMessage: (string) $response['body'],
                    errorType: WebhookLog::ERROR_SERVER,
                    httpStatus: $response['http_status'],
                );
            }

            $this->webhookLog->status = 'failed';
            $this->webhookLog->error_message = (string) $response['body'];
            $this->webhookLog->error_type = 'remote_error';
        }

        $this->webhookLog->payload_out = $response;
        $this->webhookLog->http_status = $response['http_status'];
        $this->webhookLog->response_body = $response['body'];
        $this->webhookLog->processing_ms = $processingMs;
        $this->webhookLog->save();
    }

    private function markAsFailed(Throwable $exception, float $startedAt): void
    {
        $processingMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (method_exists($this->webhookLog, 'markAsFailed')) {
            $this->webhookLog->markAsFailed(
                errorMessage: $exception->getMessage(),
                errorType: WebhookLog::ERROR_UNKNOWN,
                httpStatus: (int) ($this->webhookLog->http_status ?? 0),
            );
        }

        $this->webhookLog->status = 'failed';
        $this->webhookLog->error_message = $exception->getMessage();
        $this->webhookLog->error_type = $exception::class;
        $this->webhookLog->processing_ms = $processingMs;
        $this->webhookLog->save();
    }

    /**
     * @param  array<string,mixed>  $parsed
     */
    private function resolveNotificationMessage(array $parsed): string
    {
        $event = (string) ($parsed['event'] ?? 'unknown');
        $rules = NotificationRule::query()->active()->forEvent($event)->orderBy('sort_order')->get();

        foreach ($rules as $rule) {
            if (! $rule->matchesCondition($parsed)) {
                continue;
            }

            $message = trim($rule->renderMessage($parsed));
            if ($message !== '') {
                return $message;
            }
        }

        return (string) ($parsed['message'] ?? 'Tu solicitud fue actualizada por un agente.');
    }

    /**
     * @param  array<string,mixed>  $source
     * @return array<string,mixed>
     */
    private function applyDynamicMappings(array $source): array
    {
        $mappings = FieldMapping::getMappings('bitrix24');
        if ($mappings->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($mappings as $mapping) {
            $value = data_get($source, (string) $mapping->source_path);
            $transformed = $mapping->applyTransform($value);
            if ($transformed === null || $transformed === '') {
                continue;
            }
            $result[(string) $mapping->target_field] = $transformed;
        }

        return $result;
    }
}
