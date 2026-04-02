<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\FieldMapping;
use App\Models\FailedWebhook;
use App\Models\WebhookLog;
use App\Services\Bitrix24Service;
use App\Services\BotmakerService;
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
    ): void
    {
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
        $targetUrl = (string) config_dynamic('bitrix24.webhook_url', config('services.bitrix24.webhook_url'));

        FailedWebhook::createFromLog(
            $this->webhookLog,
            $payload,
            $targetUrl,
            $exception->getMessage(),
            (int) ($this->webhookLog->http_status ?? 0),
        );
    }

    /**
     * @param mixed $payload
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
     * @param array<string, mixed> $parsed
     * @return array<string, mixed>
     */
    private function mapLeadData(array $parsed): array
    {
        $dynamicMappings = FieldMapping::getMappings('botmaker');
        if ($dynamicMappings->isNotEmpty()) {
            $mapped = $this->applyDynamicMappings($parsed, $dynamicMappings->all());
            if ($mapped !== []) {
                return $mapped;
            }
        }

        $firstName = trim((string) ($parsed['first_name'] ?? ''));
        $lastName = trim((string) ($parsed['last_name'] ?? ''));
        $middleLastName = trim((string) ($parsed['middle_last_name'] ?? ''));
        $fullName = trim($firstName.' '.$lastName.' '.$middleLastName);
        $phone = trim((string) ($parsed['phone'] ?? ''));
        $message = trim((string) ($parsed['message'] ?? ''));
        $event = (string) ($parsed['event'] ?? 'Lead desde Botmaker');
        $currency = (string) config_dynamic('botmaker.salary_currency', config('integrations.botmaker_to_bitrix.currency', 'MXN'));
        $bitrixFields = config_dynamic('botmaker.bitrix_fields', config('integrations.botmaker_to_bitrix.bitrix_fields', []));

        if (! is_array($bitrixFields)) {
            return [];
        }

        $canonical = [
            'title' => $fullName !== '' ? "Lead Botmaker - {$fullName}" : $event,
            'comments' => $message,
            'phone' => $phone,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_last_name' => $middleLastName,
            'birth_date' => (string) ($parsed['birth_date'] ?? ''),
            'weeks_quoted' => (string) ($parsed['weeks_quoted'] ?? ''),
            'employment_status' => (string) ($parsed['employment_status'] ?? ''),
            'last_salary' => (string) ($parsed['last_salary'] ?? ''),
            'state' => (string) ($parsed['state'] ?? ''),
        ];

        $lead = [];

        foreach ($canonical as $sourceKey => $rawValue) {
            $targets = $this->getTargetsFor($bitrixFields, $sourceKey);
            if ($targets === []) {
                continue;
            }

            $value = $rawValue;

            if (in_array($sourceKey, ['weeks_quoted', 'employment_status', 'state'], true)) {
                $value = $this->mapEnumValue($sourceKey, (string) $rawValue);
            }

            if ($sourceKey === 'birth_date') {
                $value = $this->normalizeDate((string) $rawValue);
            }

            if ($sourceKey === 'last_salary') {
                $value = $this->normalizeMoney((string) $rawValue, $currency);
            }

            if ($sourceKey === 'phone') {
                $value = $this->normalizePhoneField((string) $rawValue);
            }

            if ($value === null || $value === '') {
                continue;
            }

            foreach ($targets as $targetKey) {
                $lead[$targetKey] = $value;
            }
        }

        return $lead;
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date instanceof \DateTimeImmutable) {
                return $date->format('Y-m-d');
            }
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeMoney(string $value, string $currency): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $normalized = str_replace([',', '$', 'MXN', 'mxn', ' '], '', $value);
        $numeric = preg_replace('/[^\d.]/', '', $normalized) ?? '';
        if ($numeric === '') {
            return null;
        }

        return $numeric.'|'.strtoupper(trim($currency));
    }

    /**
     * @param array<string, mixed> $bitrixFields
     * @return array<int, string>
     */
    private function getTargetsFor(array $bitrixFields, string $sourceKey): array
    {
        $value = $bitrixFields[$sourceKey] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return [trim($value)];
        }

        if (is_array($value)) {
            $targets = array_values(array_filter(
                array_map(static fn (mixed $item): string => is_string($item) ? trim($item) : '', $value),
                static fn (string $item): bool => $item !== '',
            ));

            return $targets;
        }

        return [];
    }

    private function mapEnumValue(string $sourceKey, string $value): ?string
    {
        $normalized = $this->normalizeText($value);
        if ($normalized === '') {
            return null;
        }

        $enumMaps = config_dynamic('botmaker.enum_maps', config('integrations.botmaker_to_bitrix.enum_maps', []));
        $map = is_array($enumMaps) ? ($enumMaps[$sourceKey] ?? []) : [];
        if (! is_array($map)) {
            return null;
        }

        foreach ($map as $label => $id) {
            if (! is_string($label)) {
                continue;
            }

            if ($this->normalizeText($label) === $normalized) {
                return is_string($id) || is_numeric($id) ? (string) $id : null;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{VALUE: string, VALUE_TYPE: string}>|null
     */
    private function normalizePhoneField(string $value): ?array
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return null;
        }

        return [[
            'VALUE' => $digits,
            'VALUE_TYPE' => 'WORK',
        ]];
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $base = $ascii !== false ? $ascii : $value;
        $upper = strtoupper($base);

        return preg_replace('/\s+/', ' ', $upper) ?? $upper;
    }

    /**
     * @param array<string,mixed> $source
     * @param array<int, \App\Models\FieldMapping> $mappings
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
     * @param array{success: bool, http_status: int, body: string} $response
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
