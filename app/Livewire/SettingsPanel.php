<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Setting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SettingsPanel extends Component
{
    public string $botmakerApiUrl = '';

    public string $botmakerApiToken = '';

    public string $botmakerWebhookSecret = '';

    public string $bitrix24WebhookUrl = '';

    public string $bitrix24WebhookSecret = '';

    public string $botmakerSalaryCurrency = 'MXN';

    public string $botmakerSourceAliasesJson = '';

    public string $botmakerBitrixFieldsJson = '';

    public string $botmakerEnumMapsJson = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public ?string $botmakerTestMessage = null;

    public bool $botmakerTestOk = false;

    public ?string $bitrixTestMessage = null;

    public bool $bitrixTestOk = false;

    public string $retryMaxAttempts = '5';

    public string $retryBackoffSchedule = '30,60,300,900,3600';

    public string $retryHttpTimeout = '15';

    public function mount(): void
    {
        $this->botmakerApiUrl = (string) config_dynamic('botmaker.api_url', config('services.botmaker.api_url', ''));
        $this->botmakerApiToken = (string) config_dynamic('botmaker.api_token', config('services.botmaker.api_token', ''));
        $this->botmakerWebhookSecret = (string) config_dynamic('botmaker.webhook_secret', config('services.botmaker.webhook_secret', ''));
        $this->bitrix24WebhookUrl = (string) config_dynamic('bitrix24.webhook_url', config('services.bitrix24.webhook_url', ''));
        $this->bitrix24WebhookSecret = (string) config_dynamic('bitrix24.webhook_secret', config('services.bitrix24.webhook_secret', ''));
        $this->botmakerSalaryCurrency = (string) config_dynamic('botmaker.salary_currency', config('integrations.botmaker_to_bitrix.currency', 'MXN'));
        $this->botmakerSourceAliasesJson = $this->jsonForEditor(config_dynamic('botmaker.source_aliases', config('integrations.botmaker_to_bitrix.source_aliases', [])));
        $this->botmakerBitrixFieldsJson = $this->jsonForEditor(config_dynamic('botmaker.bitrix_fields', config('integrations.botmaker_to_bitrix.bitrix_fields', [])));
        $this->botmakerEnumMapsJson = $this->jsonForEditor(config_dynamic('botmaker.enum_maps', config('integrations.botmaker_to_bitrix.enum_maps', [])));
        $this->retryMaxAttempts = (string) config_dynamic('retry.max_attempts', 5);
        $this->retryBackoffSchedule = implode(',', (array) config_dynamic('retry.backoff_schedule', [30, 60, 300, 900, 3600]));
        $this->retryHttpTimeout = (string) config_dynamic('retry.http_timeout', 15);
    }

    public function save(): void
    {
        $this->reset('successMessage', 'errorMessage');

        $validated = $this->validate([
            'botmakerApiUrl' => ['required', 'url'],
            'botmakerApiToken' => ['required', 'string', 'max:500'],
            'botmakerWebhookSecret' => ['required', 'string', 'max:500'],
            'bitrix24WebhookUrl' => ['required', 'url'],
            'bitrix24WebhookSecret' => ['required', 'string', 'max:500'],
            'botmakerSalaryCurrency' => ['required', 'string', 'min:3', 'max:3'],
            'botmakerSourceAliasesJson' => ['required', 'string'],
            'botmakerBitrixFieldsJson' => ['required', 'string'],
            'botmakerEnumMapsJson' => ['required', 'string'],
            'retryMaxAttempts' => ['required', 'integer', 'min:1', 'max:10'],
            'retryBackoffSchedule' => ['required', 'string'],
            'retryHttpTimeout' => ['required', 'integer', 'min:5', 'max:60'],
        ], [], [
            'botmakerApiUrl' => 'Botmaker API URL',
            'botmakerApiToken' => 'Token API de Botmaker',
            'botmakerWebhookSecret' => 'Secreto de webhook de Botmaker',
            'bitrix24WebhookUrl' => 'URL del webhook de Bitrix24',
            'bitrix24WebhookSecret' => 'Secreto de webhook de Bitrix24',
            'botmakerSalaryCurrency' => 'Moneda de salario',
            'botmakerSourceAliasesJson' => 'Alias de origen',
            'botmakerBitrixFieldsJson' => 'Mapeo de campos Bitrix',
            'botmakerEnumMapsJson' => 'Mapeo de catálogos',
            'retryMaxAttempts' => 'Máximo de intentos',
            'retryBackoffSchedule' => 'Backoff',
            'retryHttpTimeout' => 'Timeout HTTP',
        ]);

        try {
            $sourceAliases = $this->decodeJsonOrFail((string) $validated['botmakerSourceAliasesJson'], 'Alias de origen');
            $bitrixFields = $this->decodeJsonOrFail((string) $validated['botmakerBitrixFieldsJson'], 'Mapeo de campos Bitrix');
            $enumMaps = $this->decodeJsonOrFail((string) $validated['botmakerEnumMapsJson'], 'Mapeo de catálogos');
            $currency = strtoupper((string) $validated['botmakerSalaryCurrency']);
            $backoff = array_values(array_filter(array_map(
                static fn (string $value): int => (int) trim($value),
                explode(',', (string) $validated['retryBackoffSchedule']),
            ), static fn (int $value): bool => $value > 0));
            if ($backoff === []) {
                throw new \RuntimeException('Backoff inválido. Usa valores separados por coma.');
            }

            Setting::set('botmaker.api_url', (string) $validated['botmakerApiUrl']);
            Setting::set('botmaker.api_token', (string) $validated['botmakerApiToken']);
            Setting::set('botmaker.webhook_secret', (string) $validated['botmakerWebhookSecret']);
            Setting::set('bitrix24.webhook_url', (string) $validated['bitrix24WebhookUrl']);
            Setting::set('bitrix24.webhook_secret', (string) $validated['bitrix24WebhookSecret']);
            Setting::set('botmaker.salary_currency', $currency);
            Setting::set('botmaker.source_aliases', $sourceAliases, 'json');
            Setting::set('botmaker.bitrix_fields', $bitrixFields, 'json');
            Setting::set('botmaker.enum_maps', $enumMaps, 'json');
            Setting::set('retry.max_attempts', (int) $validated['retryMaxAttempts'], 'integer');
            Setting::set('retry.backoff_schedule', $backoff, 'json');
            Setting::set('retry.http_timeout', (int) $validated['retryHttpTimeout'], 'integer');

            $this->botmakerSalaryCurrency = $currency;
            $this->botmakerSourceAliasesJson = $this->jsonForEditor($sourceAliases);
            $this->botmakerBitrixFieldsJson = $this->jsonForEditor($bitrixFields);
            $this->botmakerEnumMapsJson = $this->jsonForEditor($enumMaps);
            $this->retryBackoffSchedule = implode(',', $backoff);
            $this->successMessage = 'Configuración guardada correctamente.';
        } catch (\Throwable $exception) {
            $this->errorMessage = 'No se pudo guardar la configuración: ' . $exception->getMessage();
        }
    }

    public function testBotmakerConnection(): void
    {
        $this->botmakerTestMessage = null;
        $this->botmakerTestOk = false;

        $url = rtrim($this->botmakerApiUrl !== '' ? $this->botmakerApiUrl : (string) config('services.botmaker.api_url', ''), '/');
        $token = trim($this->botmakerApiToken !== '' ? $this->botmakerApiToken : (string) config('services.botmaker.api_token', ''));

        if ($url === '' || $token === '') {
            $this->botmakerTestMessage = 'Error: configura API URL y API Token de Botmaker.';

            return;
        }

        $client = new Client(['timeout' => 10]);

        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json',
                ],
            ]);

            $status = $response->getStatusCode();
            $this->botmakerTestOk = $status >= 200 && $status < 300;
            $this->botmakerTestMessage = $this->botmakerTestOk
                ? "Conexión exitosa (HTTP {$status})"
                : "Error: HTTP {$status}";
        } catch (RequestException $exception) {
            $status = $exception->getResponse()?->getStatusCode();
            $reason = $exception->getResponse()?->getReasonPhrase() ?: $exception->getMessage();
            $this->botmakerTestMessage = $status !== null
                ? "Error: HTTP {$status} - {$reason}"
                : "Error de conexión: {$reason}";
        } catch (\Throwable $exception) {
            $this->botmakerTestMessage = 'Error de conexión: '.$exception->getMessage();
        }
    }

    public function testBitrix24Connection(): void
    {
        $this->bitrixTestMessage = null;
        $this->bitrixTestOk = false;

        $baseUrl = rtrim($this->bitrix24WebhookUrl !== '' ? $this->bitrix24WebhookUrl : (string) config('services.bitrix24.webhook_url', ''), '/');
        if ($baseUrl === '') {
            $this->bitrixTestMessage = 'Error: configura la URL del webhook de Bitrix24.';

            return;
        }

        $client = new Client(['timeout' => 10]);
        $url = "{$baseUrl}/crm.lead.list?start=0&limit=1";

        try {
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $status = $response->getStatusCode();
            $this->bitrixTestOk = $status >= 200 && $status < 300;
            $this->bitrixTestMessage = $this->bitrixTestOk
                ? "Conexión exitosa (HTTP {$status})"
                : "Error: HTTP {$status}";
        } catch (RequestException $exception) {
            $status = $exception->getResponse()?->getStatusCode();
            $reason = $exception->getResponse()?->getReasonPhrase() ?: $exception->getMessage();
            $this->bitrixTestMessage = $status !== null
                ? "Error: HTTP {$status} - {$reason}"
                : "Error de conexión: {$reason}";
        } catch (\Throwable $exception) {
            $this->bitrixTestMessage = 'Error de conexión: '.$exception->getMessage();
        }
    }

    /**
     * @param mixed $value
     */
    private function jsonForEditor(mixed $value): string
    {
        if (! is_array($value)) {
            return '{}';
        }

        return (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonOrFail(string $json, string $label): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException("{$label}: JSON invalido.");
        }

        return $decoded;
    }

    public function render(): View
    {
        return view('livewire.settings-panel')
            ->layout('layouts.app', [
                'title' => 'Configuración',
            ]);
    }
}
