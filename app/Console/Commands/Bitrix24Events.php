<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Bitrix24ConnectorService;
use Illuminate\Console\Command;
use Throwable;

class Bitrix24Events extends Command
{
    protected $signature = 'bitrix24:events {--event=OnImConnectorMessageAdd : Evento a consultar}';

    protected $description = 'Muestra handlers registrados en Bitrix24 via event.get';

    public function handle(Bitrix24ConnectorService $connector): int
    {
        $event = (string) $this->option('event');

        try {
            $result = $connector->getBoundEvents($event);
            $data = $result['data'] ?? [];
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
