<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FailedWebhook;
use App\Models\WebhookLog;
use App\Support\MapBotmakerCanonicalToBitrixLead;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Throwable;

final class IntegrationProbeService
{
    public function __construct(
        private readonly Bitrix24Service $bitrix24Service,
    ) {}

    /**
     * Crea un lead de demostración con el mapeo estándar (igual que bitrix:test-lead).
     *
     * @return array{
     *     success: bool,
     *     http_status: int,
     *     lead_id: int|null,
     *     body: string,
     *     fields: array<string, mixed>,
     *     base_url: string,
     *     config_warning: string|null
     * }
     */
    public function runBitrixSampleLead(string $originLabel = 'webservice'): array
    {
        $baseUrl = rtrim((string) config_dynamic('bitrix24.webhook_url', config('services.bitrix24.webhook_url', '')), '/');

        $configWarning = null;
        if ($baseUrl === '' || str_contains($baseUrl, 'dominio.bitrix24.com')) {
            $configWarning = 'BITRIX24_WEBHOOK_URL no parece un portal real; revisa .env o Configuración.';
        }

        $suffix = (string) now()->format('YmdHis');

        $parsed = [
            'first_name' => 'Juan Carlos',
            'last_name' => 'Pérez',
            'middle_last_name' => 'López',
            'phone' => '+52 55 5123 4567',
            'email' => 'prueba.lead+'.$suffix.'@example.com',
            'message' => "Lead de prueba ({$originLabel}, {$suffix}). Mapeo completo vía IntegrationProbeService.",
            'event' => 'prueba_'.$originLabel.'_'.$suffix,
            'birth_date' => '15/05/1990',
            'weeks_quoted' => 'ENTRE 500 Y 1000',
            'employment_status' => 'ACTIVO',
            'last_salary' => '$18,500.50 MXN',
            'state' => 'JALISCO',
        ];

        $fields = MapBotmakerCanonicalToBitrixLead::fromParsed($parsed);
        $safeLabel = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $originLabel) ?: 'test';
        $fields['TITLE'] = "Prueba {$safeLabel} {$suffix}";

        $response = $this->bitrix24Service->createLead($fields);

        $leadId = null;
        $decoded = json_decode($response['body'], true);
        if (is_array($decoded) && isset($decoded['result']) && is_numeric($decoded['result'])) {
            $leadId = (int) $decoded['result'];
        }

        return [
            'success' => $response['success'],
            'http_status' => $response['http_status'],
            'lead_id' => $leadId,
            'body' => $response['body'],
            'fields' => $fields,
            'base_url' => $baseUrl,
            'config_warning' => $configWarning,
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function probeBotmakerApi(): array
    {
        $baseUrl = rtrim((string) config_dynamic('botmaker.api_url', config('services.botmaker.api_url', '')), '/');
        $token = trim((string) config_dynamic('botmaker.api_token', config('services.botmaker.api_token', '')));

        if ($baseUrl === '' || $token === '') {
            return ['ok' => false, 'message' => 'ERROR (config incompleta)'];
        }

        $client = new Client(['timeout' => 10]);

        try {
            $response = $client->request('GET', $baseUrl, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json',
                ],
            ]);
            $status = $response->getStatusCode();

            return [
                'ok' => $status >= 200 && $status < 300,
                'message' => "OK (HTTP {$status})",
            ];
        } catch (RequestException $exception) {
            $status = $exception->getResponse()?->getStatusCode();

            return [
                'ok' => false,
                'message' => $status !== null ? "ERROR (HTTP {$status})" : 'ERROR (sin respuesta)',
            ];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => 'ERROR ('.$exception->getMessage().')'];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function probeBitrixApi(): array
    {
        $baseUrl = rtrim((string) config_dynamic('bitrix24.webhook_url', config('services.bitrix24.webhook_url', '')), '/');
        if ($baseUrl === '') {
            return ['ok' => false, 'message' => 'ERROR (config incompleta)'];
        }

        $client = new Client(['timeout' => 10]);
        $url = "{$baseUrl}/crm.lead.list?start=0&limit=1";

        try {
            $response = $client->request('GET', $url, [
                'headers' => ['Accept' => 'application/json'],
            ]);
            $status = $response->getStatusCode();

            return [
                'ok' => $status >= 200 && $status < 300,
                'message' => "OK (HTTP {$status})",
            ];
        } catch (RequestException $exception) {
            $status = $exception->getResponse()?->getStatusCode();

            return [
                'ok' => false,
                'message' => $status !== null ? "ERROR (HTTP {$status})" : 'ERROR (sin respuesta)',
            ];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => 'ERROR ('.$exception->getMessage().')'];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function probeQueueStuck(): array
    {
        $stuckCount = WebhookLog::query()
            ->whereIn('status', [WebhookLog::STATUS_RECEIVED, WebhookLog::STATUS_PROCESSING])
            ->where('created_at', '<=', now()->subMinutes(10))
            ->count();

        if ($stuckCount > 0) {
            return ['ok' => false, 'message' => "ATENCION: {$stuckCount} registros atorados (>10 min)"];
        }

        return ['ok' => true, 'message' => 'OK'];
    }

    /**
     * @return array{
     *     today: array{total: int, sent: int, failed: int, in_queue: int},
     *     failed_pending: int
     * }
     */
    public function webhookSummaryToday(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return [
            'today' => [
                'total' => WebhookLog::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count(),
                'sent' => WebhookLog::query()->whereBetween('created_at', [$todayStart, $todayEnd])->where('status', WebhookLog::STATUS_SENT)->count(),
                'failed' => WebhookLog::query()->whereBetween('created_at', [$todayStart, $todayEnd])->failed()->count(),
                'in_queue' => WebhookLog::query()->whereBetween('created_at', [$todayStart, $todayEnd])->whereIn('status', [WebhookLog::STATUS_RECEIVED, WebhookLog::STATUS_PROCESSING])->count(),
            ],
            'failed_pending' => FailedWebhook::query()->pending()->count(),
        ];
    }
}
