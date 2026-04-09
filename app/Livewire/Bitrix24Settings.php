<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use App\Models\Bitrix24Token;
use App\Models\Setting;
use App\Services\Bitrix24AuthService;
use App\Services\Bitrix24ConnectorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Throwable;

class Bitrix24Settings extends Component
{
    // v2 OAuth fields
    public string $domain = '';

    public string $clientId = '';

    public string $clientSecret = '';

    public string $connectorId = '';

    public string $lineId = '';

    // Legacy v1 fields
    public string $bitrix24WebhookUrl = '';

    public string $defaultSourceId = '';

    public string $defaultAssignedById = '';

    public string $defaultStatusId = '';

    // UI state
    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public ?string $testMessage = null;

    public bool $testOk = false;

    public ?string $setupMessage = null;

    public bool $setupOk = false;

    public bool $setupRunning = false;

    public bool $showLegacy = false;

    public function mount(): void
    {
        $this->domain = (string) config_dynamic('bitrix24.domain', config('services.bitrix24.domain', ''));
        $this->clientId = (string) config_dynamic('bitrix24.client_id', config('services.bitrix24.client_id', ''));
        $this->clientSecret = (string) config_dynamic('bitrix24.client_secret', config('services.bitrix24.client_secret', ''));
        $this->connectorId = (string) config_dynamic('bitrix24.connector_id', config('services.bitrix24.connector_id', 'botmaker_whatsapp'));
        $this->lineId = (string) config_dynamic('bitrix24.line_id', config('services.bitrix24.line_id', '1'));

        $this->bitrix24WebhookUrl = (string) config_dynamic('bitrix24.webhook_url', config('services.bitrix24.webhook_url', ''));
        if (trim($this->bitrix24WebhookUrl) === '' && Schema::hasTable('authorized_tokens')) {
            $fromToken = AuthorizedToken::getWebhookUrl('bitrix24');
            if (is_string($fromToken) && trim($fromToken) !== '') {
                $this->bitrix24WebhookUrl = trim($fromToken);
            }
        }
        $this->defaultSourceId = (string) config_dynamic('bitrix24.default_source_id', '');
        $this->defaultAssignedById = (string) config_dynamic('bitrix24.default_assigned_by_id', '');
        $this->defaultStatusId = (string) config_dynamic('bitrix24.default_status_id', '');
    }

    public function saveOAuth(): void
    {
        $this->reset('successMessage', 'errorMessage');

        $validated = $this->validate([
            'domain' => ['required', 'string', 'max:255'],
            'clientId' => ['required', 'string', 'max:255'],
            'clientSecret' => ['required', 'string', 'max:255'],
            'connectorId' => ['required', 'string', 'max:100'],
            'lineId' => ['required', 'string', 'max:20'],
        ], [], [
            'domain' => 'Dominio Bitrix24',
            'clientId' => 'Client ID',
            'clientSecret' => 'Client Secret',
            'connectorId' => 'Connector ID',
            'lineId' => 'Line ID',
        ]);

        try {
            Setting::set('bitrix24.domain', (string) $validated['domain']);
            Setting::set('bitrix24.client_id', (string) $validated['clientId']);
            Setting::set('bitrix24.client_secret', (string) $validated['clientSecret']);
            Setting::set('bitrix24.connector_id', (string) $validated['connectorId']);
            Setting::set('bitrix24.line_id', (string) $validated['lineId']);

            $this->successMessage = 'Configuración OAuth v2 guardada correctamente.';
        } catch (Throwable $e) {
            $this->errorMessage = 'No se pudo guardar: ' . $e->getMessage();
        }
    }

    public function saveLegacy(): void
    {
        $this->reset('successMessage', 'errorMessage');

        $validated = $this->validate([
            'bitrix24WebhookUrl' => ['nullable', 'url'],
            'defaultSourceId' => ['nullable', 'string', 'max:80'],
            'defaultAssignedById' => ['nullable', 'string', 'max:20'],
            'defaultStatusId' => ['nullable', 'string', 'max:50'],
        ], [], [
            'bitrix24WebhookUrl' => 'URL del webhook entrante',
            'defaultSourceId' => 'Fuente del lead',
            'defaultAssignedById' => 'Responsable',
            'defaultStatusId' => 'Estatus inicial',
        ]);

        try {
            Setting::set('bitrix24.webhook_url', (string) ($validated['bitrix24WebhookUrl'] ?? ''));
            Setting::set('bitrix24.default_source_id', trim((string) ($validated['defaultSourceId'] ?? '')));
            Setting::set('bitrix24.default_assigned_by_id', trim((string) ($validated['defaultAssignedById'] ?? '')));
            Setting::set('bitrix24.default_status_id', trim((string) ($validated['defaultStatusId'] ?? '')));

            $this->successMessage = 'Configuración Legacy v1 guardada.';
        } catch (Throwable $e) {
            $this->errorMessage = 'No se pudo guardar: ' . $e->getMessage();
        }
    }

    public function testOAuthConnection(): void
    {
        $this->testMessage = null;
        $this->testOk = false;

        try {
            $authService = app(Bitrix24AuthService::class);
            $accessToken = $authService->getValidToken();

            $this->testOk = true;
            $this->testMessage = 'Token OAuth válido obtenido correctamente. Token: ' . substr($accessToken, 0, 8) . '...';
        } catch (Throwable $e) {
            $this->testMessage = 'Error OAuth: ' . $e->getMessage();
        }
    }

    public function testConnectorStatus(): void
    {
        $this->testMessage = null;
        $this->testOk = false;

        try {
            $connector = app(Bitrix24ConnectorService::class);
            $lineId = (int) config_dynamic('bitrix24.line_id', config('services.bitrix24.line_id', '1'));
            $result = $connector->checkStatus($lineId);

            $active = $result['data']['result']['active_connector'] ?? $result['data']['result'] ?? null;
            $this->testOk = true;
            $this->testMessage = 'imconnector.status OK — Estado: ' . json_encode($active);
        } catch (Throwable $e) {
            $this->testMessage = 'Error imconnector.status: ' . $e->getMessage();
        }
    }

    public function setupConnector(): void
    {
        $this->setupMessage = null;
        $this->setupOk = false;
        $this->setupRunning = true;

        $lineId = (int) config_dynamic('bitrix24.line_id', config('services.bitrix24.line_id', '1'));
        $steps = [];

        try {
            $connector = app(Bitrix24ConnectorService::class);

            // Step 1: Register
            try {
                $connector->registerConnector();
                $steps[] = 'Registro: OK';
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), 'ALREADY')) {
                    $steps[] = 'Registro: ya existente (OK)';
                } else {
                    throw $e;
                }
            }

            // Step 2: Activate
            try {
                $connector->activateConnector($lineId);
                $steps[] = 'Activación línea ' . $lineId . ': OK';
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), 'ALREADY')) {
                    $steps[] = 'Activación línea ' . $lineId . ': ya activo (OK)';
                } else {
                    throw $e;
                }
            }

            // Step 3: Set connector data
            $connector->setConnectorData($lineId);
            $steps[] = 'Datos del conector: OK';

            // Step 4: Bind events
            try {
                $connector->bindRequiredEvents();
                $steps[] = 'Eventos vinculados: OK';
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), 'ALREADY')) {
                    $steps[] = 'Eventos: ya vinculados (OK)';
                } else {
                    $steps[] = 'Eventos: ' . $e->getMessage();
                }
            }

            // Verify
            $status = $connector->checkStatus($lineId);
            $active = $status['data']['result']['active_connector'] ?? $status['data']['result'] ?? 'unknown';
            $steps[] = 'Estado final: ' . json_encode($active);

            $this->setupOk = true;
            $this->setupMessage = implode(' | ', $steps);
        } catch (Throwable $e) {
            $steps[] = 'Error: ' . $e->getMessage();
            $this->setupMessage = implode(' | ', $steps);
        }

        $this->setupRunning = false;
    }

    public function toggleLegacy(): void
    {
        $this->showLegacy = ! $this->showLegacy;
    }

    public function getTokenStatusProperty(): ?array
    {
        $token = Bitrix24Token::getActive();

        if (! $token instanceof Bitrix24Token) {
            return null;
        }

        return [
            'domain' => $token->domain,
            'expired' => $token->isExpired(),
            'expires_at' => $token->expires_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'updated_at' => $token->updated_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
        ];
    }

    public function render(): View
    {
        return view('livewire.bitrix24-settings')
            ->layout('layouts.app', ['title' => 'Conexión Bitrix24 v2']);
    }
}
