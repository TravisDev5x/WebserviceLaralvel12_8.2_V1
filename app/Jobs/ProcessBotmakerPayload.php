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

    public function __construct(public WebhookLog $webhookLog)
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
        $startedAt = microtime(true);

        $this->markAsProcessing();

        $payload = $this->normalizePayload($this->webhookLog->payload_in);
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
        $targetUrl = AuthorizedToken::resolvedBitrix24WebhookUrl();

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
}
