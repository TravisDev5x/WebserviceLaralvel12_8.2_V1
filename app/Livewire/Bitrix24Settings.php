<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use App\Models\Setting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Bitrix24Settings extends Component
{
    public string $bitrix24WebhookUrl = '';

    public string $defaultSourceId = '';

    public string $defaultAssignedById = '';

    public string $defaultStatusId = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public ?string $testMessage = null;

    public bool $testOk = false;

    public ?string $lastTestAt = null;

    public function mount(): void
    {
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

    public function save(): void
    {
        $this->reset('successMessage', 'errorMessage');

        $validated = $this->validate([
            'bitrix24WebhookUrl' => ['required', 'url'],
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
            Setting::set('bitrix24.webhook_url', (string) $validated['bitrix24WebhookUrl']);
            Setting::set('bitrix24.default_source_id', trim((string) ($validated['defaultSourceId'] ?? '')));
            Setting::set('bitrix24.default_assigned_by_id', trim((string) ($validated['defaultAssignedById'] ?? '')));
            Setting::set('bitrix24.default_status_id', trim((string) ($validated['defaultStatusId'] ?? '')));

            $this->successMessage = 'Configuración de Bitrix24 guardada.';
        } catch (\Throwable $e) {
            $this->errorMessage = 'No se pudo guardar: '.$e->getMessage();
        }
    }

    public function testConnection(): void
    {
        $this->testMessage = null;
        $this->testOk = false;

        $base = rtrim($this->bitrix24WebhookUrl !== '' ? $this->bitrix24WebhookUrl : AuthorizedToken::resolvedBitrix24WebhookUrl(), '/');

        if ($base === '') {
            $this->testMessage = 'Configura la URL del webhook o un registro entrante en Webhooks autorizados.';

            return;
        }

        $client = new Client(['timeout' => 10]);
        $url = "{$base}/crm.lead.list?start=0&limit=1";

        try {
            $response = $client->request('GET', $url, ['headers' => ['Accept' => 'application/json']]);
            $status = $response->getStatusCode();
            $this->testOk = $status >= 200 && $status < 300;
            $this->testMessage = $this->testOk
                ? "Conexión correcta (HTTP {$status})"
                : "Error HTTP {$status}";
        } catch (RequestException $exception) {
            $status = $exception->getResponse()?->getStatusCode();
            $reason = $exception->getResponse()?->getReasonPhrase() ?: $exception->getMessage();
            $this->testMessage = $status !== null
                ? "Error HTTP {$status} - {$reason}"
                : "Error de red: {$reason}";
        } catch (\Throwable $exception) {
            $this->testMessage = 'Error: '.$exception->getMessage();
        }

        $this->lastTestAt = now()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        session([
            'health_bitrix_ok' => $this->testOk,
            'health_bitrix_at' => $this->lastTestAt,
        ]);
    }

    public function render(): View
    {
        return view('livewire.bitrix24-settings')
            ->layout('layouts.app', ['title' => 'Conexión Bitrix24']);
    }
}
