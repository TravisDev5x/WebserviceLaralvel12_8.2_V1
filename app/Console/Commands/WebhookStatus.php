<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FailedWebhook;
use App\Models\WebhookLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class WebhookStatus extends Command
{
    protected $signature = 'webhook:status';

    protected $description = 'Muestra estado general de webhooks e integraciones';

    public function handle(): int
    {
        $this->newLine();
        $this->info('=== Estado del Middleware de Webhooks ===');

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $totalToday = WebhookLog::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $sentToday = WebhookLog::query()->whereBetween('created_at', [$todayStart, $todayEnd])->where('status', WebhookLog::STATUS_SENT)->count();
        $failedToday = WebhookLog::query()->whereBetween('created_at', [$todayStart, $todayEnd])->failed()->count();
        $inQueueToday = WebhookLog::query()->whereBetween('created_at', [$todayStart, $todayEnd])->whereIn('status', [WebhookLog::STATUS_RECEIVED, WebhookLog::STATUS_PROCESSING])->count();
        $failedPending = FailedWebhook::query()->pending()->count();

        $this->newLine();
        $this->info('Sección 1 — Resumen del día');
        $this->table(['Metricas', 'Valor'], [
            ['Total webhooks hoy', (string) $totalToday],
            ['Exitosos (sent)', (string) $sentToday],
            ['Fallidos (failed)', (string) $failedToday],
            ['En cola (processing/received)', (string) $inQueueToday],
            ['Failed webhooks pendientes de reintento', (string) $failedPending],
        ]);

        $lastReceived = WebhookLog::query()->recent(168)->latest()->first();
        $lastSuccess = WebhookLog::query()->recent(168)->where('status', WebhookLog::STATUS_SENT)->latest()->first();
        $lastFailed = WebhookLog::query()->recent(168)->failed()->latest()->first();

        $this->newLine();
        $this->info('Sección 2 — Última actividad');
        $this->line('Ultimo webhook recibido: '.$this->formatWebhookLine($lastReceived));
        $this->line('Ultimo webhook exitoso: '.$this->formatSuccessLine($lastSuccess));
        $this->line('Ultimo webhook fallido: '.$this->formatFailedLine($lastFailed));

        $this->newLine();
        $this->info('Sección 3 — Verificación de servicios');

        $botmakerCheck = $this->checkBotmaker();
        $bitrixCheck = $this->checkBitrix();
        $stuckCheck = $this->checkQueueStuck();

        $this->printHealth('Botmaker API', $botmakerCheck['ok'], $botmakerCheck['message']);
        $this->printHealth('Bitrix24 API', $bitrixCheck['ok'], $bitrixCheck['message']);
        $this->printHealth('Queue worker', $stuckCheck['ok'], $stuckCheck['message']);

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function checkBotmaker(): array
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
        } catch (\Throwable $exception) {
            return ['ok' => false, 'message' => 'ERROR ('.$exception->getMessage().')'];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function checkBitrix(): array
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
        } catch (\Throwable $exception) {
            return ['ok' => false, 'message' => 'ERROR ('.$exception->getMessage().')'];
        }
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function checkQueueStuck(): array
    {
        $stuckCount = WebhookLog::query()
            ->whereIn('status', [WebhookLog::STATUS_RECEIVED, WebhookLog::STATUS_PROCESSING])
            ->where('created_at', '<=', now()->subMinutes(10))
            ->count();

        if ($stuckCount > 0) {
            return ['ok' => false, 'message' => "ATENCION: {$stuckCount} jobs atorados"];
        }

        return ['ok' => true, 'message' => 'OK'];
    }

    private function printHealth(string $service, bool $ok, string $message): void
    {
        $line = "{$service}: {$message}";

        if ($ok) {
            $this->info($line);

            return;
        }

        if (str_starts_with($message, 'ATENCION')) {
            $this->warn($line);

            return;
        }

        $this->error($line);
    }

    private function formatWebhookLine(?WebhookLog $log): string
    {
        if ($log === null) {
            return 'Sin registros';
        }

        return sprintf(
            '[%s] [%s] [%s] [%s]',
            $this->formatDate($log->created_at),
            (string) $log->direction,
            (string) $log->source_event,
            (string) $log->status,
        );
    }

    private function formatSuccessLine(?WebhookLog $log): string
    {
        if ($log === null) {
            return 'Sin registros';
        }

        return sprintf(
            '[%s] [%s]',
            $this->formatDate($log->created_at),
            (string) $log->direction,
        );
    }

    private function formatFailedLine(?WebhookLog $log): string
    {
        if ($log === null) {
            return 'Sin registros';
        }

        $error = trim((string) $log->error_message);
        if ($error === '') {
            $error = trim((string) $log->response_body);
        }

        $short = mb_substr($error, 0, 80);

        return sprintf(
            '[%s] [%s]',
            $this->formatDate($log->created_at),
            $short !== '' ? $short : 'Sin detalle de error',
        );
    }

    private function formatDate(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
