<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\IntegrationProbeService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

class IntegrationTestPanel extends Component
{
    /** @var array<string, mixed>|null */
    public ?array $bitrixResult = null;

    /** @var array<string, mixed>|null */
    public ?array $connectivityResult = null;

    /** @var array<string, mixed>|null */
    public ?array $summary = null;

    public function mount(IntegrationProbeService $probe): void
    {
        $this->summary = $probe->webhookSummaryToday();
    }

    public function refreshSummary(IntegrationProbeService $probe): void
    {
        $this->summary = $probe->webhookSummaryToday();
    }

    public function runBitrixSampleLead(IntegrationProbeService $probe): void
    {
        try {
            $this->bitrixResult = $probe->runBitrixSampleLead('panel_web');
        } catch (Throwable $e) {
            $this->bitrixResult = [
                'success' => false,
                'http_status' => 0,
                'lead_id' => null,
                'body' => $e->getMessage(),
                'fields' => [],
                'base_url' => '',
                'config_warning' => 'Excepción al llamar a Bitrix24; revisa logs del servidor.',
            ];
        }

        try {
            $this->summary = $probe->webhookSummaryToday();
        } catch (Throwable) {
            // no bloquear la UI si el resumen falla
        }
    }

    public function runConnectivity(IntegrationProbeService $probe): void
    {
        try {
            $this->connectivityResult = [
                'botmaker' => $probe->probeBotmakerApi(),
                'bitrix' => $probe->probeBitrixApi(),
                'queue' => $probe->probeQueueStuck(),
            ];
        } catch (Throwable $e) {
            $this->connectivityResult = [
                'botmaker' => ['ok' => false, 'message' => $e->getMessage()],
                'bitrix' => ['ok' => false, 'message' => '—'],
                'queue' => ['ok' => false, 'message' => '—'],
            ];
        }

        try {
            $this->summary = $probe->webhookSummaryToday();
        } catch (Throwable) {
            //
        }
    }

    public function render(): View
    {
        return view('livewire.integration-test-panel')
            ->layout('layouts.app', [
                'title' => 'Pruebas de integración',
            ]);
    }
}
