<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Bitrix24ConnectorService;
use Illuminate\Console\Command;
use Throwable;

class SetupBitrix24Connector extends Command
{
    protected $signature = 'bitrix24:setup-connector
                            {--line= : ID de la línea de Canal Abierto (default: BITRIX24_LINE_ID del .env)}';

    protected $description = 'Registra, activa y configura el conector Botmaker WhatsApp en Bitrix24';

    public function handle(Bitrix24ConnectorService $connector): int
    {
        $lineId = (int) ($this->option('line') ?: config('services.bitrix24.line_id', 1));

        $this->newLine();
        $this->info('=== Setup del conector Botmaker WhatsApp en Bitrix24 ===');
        $this->line("Conector: " . config('services.bitrix24.connector_id'));
        $this->line("Línea de Canal Abierto: {$lineId}");
        $this->line("Dominio: " . config('services.bitrix24.domain'));
        $this->newLine();

        // Step 1: Register
        $this->info('Paso 1/3 — Registrando conector (imconnector.register)...');
        try {
            $result = $connector->registerConnector();
            $this->printResult($result);
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'CONNECTOR_ALREADY_REGISTERED') || str_contains($e->getMessage(), 'ERROR_ALREADY')) {
                $this->warn('  Conector ya registrado — continuando.');
            } else {
                $this->error("  Error: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        // Step 2: Activate
        $this->info('Paso 2/3 — Activando conector para línea (imconnector.activate)...');
        try {
            $result = $connector->activateConnector($lineId);
            $this->printResult($result);
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'CONNECTOR_ALREADY_ACTIVE') || str_contains($e->getMessage(), 'ERROR_ALREADY')) {
                $this->warn('  Conector ya activo en esta línea — continuando.');
            } else {
                $this->error("  Error: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        // Step 3: Set connector data
        $this->info('Paso 3/3 — Configurando datos del conector (imconnector.connector.data.set)...');
        try {
            $result = $connector->setConnectorData($lineId);
            $this->printResult($result);
        } catch (Throwable $e) {
            $this->error("  Error: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Step 4: Bind events
        $this->info('Paso 4 — Vinculando eventos (event.bind)...');
        try {
            $results = $connector->bindRequiredEvents();
            foreach ($results as $event => $result) {
                $note = $result['note'] ?? '';
                $this->line("  {$event}: OK" . ($note !== '' ? " ({$note})" : ''));
            }
        } catch (Throwable $e) {
            $this->warn("  Error vinculando eventos: {$e->getMessage()}");
        }

        // Verify status
        $this->newLine();
        $this->info('Verificando estado final (imconnector.status)...');
        try {
            $status = $connector->checkStatus($lineId);
            $this->printResult($status);

            $active = $status['data']['result']['active_connector'] ?? $status['data']['result'] ?? 'unknown';
            $this->line("  Estado activo: " . json_encode($active));
        } catch (Throwable $e) {
            $this->warn("  No se pudo verificar estado: {$e->getMessage()}");
        }

        $this->newLine();
        $this->info('Setup completado. Verifica en Bitrix24 → Contact Center que el conector aparece.');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function printResult(array $result): void
    {
        $success = $result['success'] ?? false;
        $httpStatus = $result['http_status'] ?? 0;

        if ($success) {
            $this->line("  OK (HTTP {$httpStatus})");
        } else {
            $this->warn("  Respuesta HTTP {$httpStatus}");
        }
    }
}
