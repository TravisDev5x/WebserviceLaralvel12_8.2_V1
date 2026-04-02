<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\IntegrationProbeService;
use Illuminate\Console\Command;

class TestBitrixLeadCommand extends Command
{
    protected $signature = 'bitrix:test-lead {--show-fields : Muestra el payload enviado a crm.lead.add}';

    protected $description = 'Crea un lead de prueba en Bitrix24 rellenando todos los campos del mapeo estándar (integración Botmaker→Bitrix)';

    public function handle(IntegrationProbeService $integrationProbeService): int
    {
        $result = $integrationProbeService->runBitrixSampleLead('CLI');

        if ($result['config_warning'] !== null) {
            $this->warn($result['config_warning']);
            $this->newLine();
        }

        if ($this->option('show-fields')) {
            $this->info('Campos que se envían a crm.lead.add:');
            $this->line(json_encode($result['fields'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->newLine();
        }

        $this->info('Enviando crm.lead.add con mapeo completo...');
        $this->line('URL base: '.$result['base_url']);

        if ($result['success']) {
            $this->info('HTTP '.$result['http_status'].' — respuesta:');
            $this->line($result['body']);

            if ($result['lead_id'] !== null) {
                $this->newLine();
                $this->info('ID del lead creado: '.(string) $result['lead_id']);
            }

            return self::SUCCESS;
        }

        $this->error('Fallo (HTTP '.$result['http_status'].')');
        $this->line($result['body']);
        $this->newLine();
        $this->warn('Si Bitrix rechaza UF_CRM_* o listas, revisa que los códigos de campo y enums coincidan con tu portal (config o BOTMAKER_*_JSON en .env).');

        return self::FAILURE;
    }
}
