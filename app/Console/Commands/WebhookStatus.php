<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebhookLog;
use App\Services\IntegrationProbeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class WebhookStatus extends Command
{
    protected $signature = 'webhook:status';

    protected $description = 'Muestra estado general de webhooks e integraciones';

    public function handle(IntegrationProbeService $integrationProbeService): int
    {
        $this->newLine();
        $this->info('=== Estado del Middleware de Webhooks ===');

        $summary = $integrationProbeService->webhookSummaryToday();

        $this->newLine();
        $this->info('Sección 1 — Resumen del día');
        $this->table(['Metricas', 'Valor'], [
            ['Total webhooks hoy', (string) $summary['today']['total']],
            ['Exitosos (sent)', (string) $summary['today']['sent']],
            ['Fallidos (failed)', (string) $summary['today']['failed']],
            ['En cola (processing/received)', (string) $summary['today']['in_queue']],
            ['Failed webhooks pendientes de reintento', (string) $summary['failed_pending']],
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

        $botmakerCheck = $integrationProbeService->probeBotmakerApi();
        $bitrixCheck = $integrationProbeService->probeBitrixApi();
        $stuckCheck = $integrationProbeService->probeQueueStuck();

        $this->printHealth('Botmaker API', $botmakerCheck['ok'], $botmakerCheck['message']);
        $this->printHealth('Bitrix24 API', $bitrixCheck['ok'], $bitrixCheck['message']);
        $this->printHealth('Queue worker', $stuckCheck['ok'], $stuckCheck['message']);

        $this->newLine();

        return self::SUCCESS;
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
