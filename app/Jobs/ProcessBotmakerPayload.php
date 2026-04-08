<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuthorizedToken;
use App\Models\FailedWebhook;
use App\Models\FieldMapping;
use App\Models\WebhookLog;
use App\Services\Bitrix24Service;
use App\Services\BotmakerService;
use App\Support\BitrixLeadDefaults;
use App\Support\MapBotmakerCanonicalToBitrixLead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessBotmakerPayload implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 300, 900, 3600];

    public int $timeout = 15;

    public function __construct(public int $webhookLogId)
    {
        $this->onQueue('webhooks');
        $this->tries = (int) config_dynamic('retry.max_attempts', 5);
        $backoff = config_dynamic('retry.backoff_schedule', [30, 60, 300, 900, 3600]);
        $this->backoff = is_array($backoff) && $backoff !== [] ? array_values(array_map('intval', $backoff)) : [30, 60, 300, 900, 3600];
        $this->timeout = (int) config_dynamic('retry.http_timeout', 15);
    }

    public function handle(
        Bitrix24Service $bitrix24Service,
        BotmakerService $botmakerService,
    ): void {
        $webhookLog = WebhookLog::query()->findOrFail($this->webhookLogId);
        $startedAt = microtime(true);

        $this->markAsProcessing($webhookLog);

        $payload = $this->normalizePayload($webhookLog->payload_in);
        $parsed = $botmakerService->parseIncomingPayload($payload);

        try {
            $phone = (string) ($parsed['phone'] ?? '');
            $contact = $phone !== '' ? $bitrix24Service->findContactByPhone($phone) : null;

            $leadData = $this->mapLeadData($parsed);

            if (is_array($contact) && isset($contact['LEAD_ID'])) {
                $response = $bitrix24Service->updateLead((int) $contact['LEAD_ID'], $leadData);
            } else {
                $response = $bitrix24Service->createLead($leadData);
            }

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
        $targetUrl = AuthorizedToken::resolvedBitrix24WebhookUrl();

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

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function mapLeadData(array $parsed): array
    {
        $dynamicMappings = FieldMapping::getMappings('botmaker');
        if ($dynamicMappings->isNotEmpty()) {
            $mapped = $this->applyDynamicMappings($parsed, $dynamicMappings->all());
            if ($mapped !== []) {
                return BitrixLeadDefaults::merge($mapped);
            }
        }

        return BitrixLeadDefaults::merge(MapBotmakerCanonicalToBitrixLead::fromParsed($parsed));
    }

    /**
     * @param  array<string,mixed>  $source
     * @param  array<int, FieldMapping>  $mappings
     * @return array<string,mixed>
     */
    private function applyDynamicMappings(array $source, array $mappings): array
    {
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
            $webhookLog->error_type = 'remote_error';
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
        $webhookLog->error_type = $exception::class;
        $webhookLog->processing_ms = $processingMs;
        $webhookLog->save();
    }
}
