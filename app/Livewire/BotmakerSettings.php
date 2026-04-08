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

class BotmakerSettings extends Component
{
    public string $botmakerApiUrl = '';

    public string $botmakerApiToken = '';

    public string $botmakerSendMessageUrl = '';

    public string $botmakerSalaryCurrency = 'MXN';

    public string $botmakerSourceAliasesJson = '';

    public string $botmakerBitrixFieldsJson = '';

    public string $botmakerEnumMapsJson = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public ?string $testMessage = null;

    public bool $testOk = false;

    public ?string $lastTestAt = null;

    public function mount(): void
    {
        $this->botmakerApiUrl = (string) config_dynamic('botmaker.api_url', config('services.botmaker.api_url', ''));
        if (trim($this->botmakerApiUrl) === '' && Schema::hasTable('authorized_tokens')) {
            $fromToken = AuthorizedToken::getWebhookUrl('botmaker');
            if (is_string($fromToken) && trim($fromToken) !== '') {
                $this->botmakerApiUrl = trim($fromToken);
            }
        }
        $this->botmakerApiToken = (string) config_dynamic('botmaker.api_token', config('services.botmaker.api_token', ''));
        $this->botmakerSendMessageUrl = (string) config_dynamic('botmaker.send_message_url', '');
        $this->botmakerSalaryCurrency = (string) config_dynamic('botmaker.salary_currency', config('integrations.botmaker_to_bitrix.currency', 'MXN'));
        $this->botmakerSourceAliasesJson = $this->jsonForEditor(config_dynamic('botmaker.source_aliases', config('integrations.botmaker_to_bitrix.source_aliases', [])));
        $this->botmakerBitrixFieldsJson = $this->jsonForEditor(config_dynamic('botmaker.bitrix_fields', config('integrations.botmaker_to_bitrix.bitrix_fields', [])));
        $this->botmakerEnumMapsJson = $this->jsonForEditor(config_dynamic('botmaker.enum_maps', config('integrations.botmaker_to_bitrix.enum_maps', [])));
    }

    public function save(): void
    {
        $this->reset('successMessage', 'errorMessage');

        $validated = $this->validate([
            'botmakerApiUrl' => ['required', 'url'],
            'botmakerApiToken' => ['nullable', 'string', 'max:2000'],
            'botmakerSendMessageUrl' => ['nullable', 'string', 'max:500'],
            'botmakerSalaryCurrency' => ['required', 'string', 'min:3', 'max:3'],
            'botmakerSourceAliasesJson' => ['required', 'string'],
            'botmakerBitrixFieldsJson' => ['required', 'string'],
            'botmakerEnumMapsJson' => ['required', 'string'],
        ], [], [
            'botmakerApiUrl' => 'URL de la API',
            'botmakerApiToken' => 'Token JWT',
            'botmakerSendMessageUrl' => 'URL de envío de mensajes',
            'botmakerSalaryCurrency' => 'Moneda',
            'botmakerSourceAliasesJson' => 'Alias de origen',
            'botmakerBitrixFieldsJson' => 'Campos Bitrix',
            'botmakerEnumMapsJson' => 'Catálogos',
        ]);

        try {
            $sourceAliases = $this->decodeJsonOrFail((string) $validated['botmakerSourceAliasesJson'], 'Alias de origen');
            $bitrixFields = $this->decodeJsonOrFail((string) $validated['botmakerBitrixFieldsJson'], 'Campos Bitrix');
            $enumMaps = $this->decodeJsonOrFail((string) $validated['botmakerEnumMapsJson'], 'Catálogos');
            $currency = strtoupper((string) $validated['botmakerSalaryCurrency']);
            $sendUrl = trim((string) ($validated['botmakerSendMessageUrl'] ?? ''));
            $token = trim((string) ($validated['botmakerApiToken'] ?? ''));

            Setting::set('botmaker.api_url', (string) $validated['botmakerApiUrl']);
            if ($token !== '') {
                Setting::set('botmaker.api_token', $token);
            }
            Setting::set('botmaker.send_message_url', $sendUrl);
            Setting::set('botmaker.salary_currency', $currency);
            Setting::set('botmaker.source_aliases', $sourceAliases, 'json');
            Setting::set('botmaker.bitrix_fields', $bitrixFields, 'json');
            Setting::set('botmaker.enum_maps', $enumMaps, 'json');

            $this->botmakerSalaryCurrency = $currency;
            $this->botmakerSourceAliasesJson = $this->jsonForEditor($sourceAliases);
            $this->botmakerBitrixFieldsJson = $this->jsonForEditor($bitrixFields);
            $this->botmakerEnumMapsJson = $this->jsonForEditor($enumMaps);
            $this->successMessage = 'Configuración de Botmaker guardada.';
        } catch (\Throwable $exception) {
            $this->errorMessage = 'No se pudo guardar: '.$exception->getMessage();
        }
    }

    public function testConnection(): void
    {
        $this->testMessage = null;
        $this->testOk = false;

        $url = rtrim($this->botmakerApiUrl !== '' ? $this->botmakerApiUrl : AuthorizedToken::resolvedBotmakerApiUrl(), '/');
        $token = trim($this->botmakerApiToken !== '' ? $this->botmakerApiToken : AuthorizedToken::resolvedBotmakerApiToken());

        if ($url === '' || $token === '') {
            $this->testMessage = 'Indica la URL de la API y el token JWT, o guarda primero la configuración.';

            return;
        }

        $client = new Client(['timeout' => 10]);

        try {
            $response = $client->request('GET', rtrim($url, '/').'/chats', [
                'headers' => [
                    'access-token' => $token,
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json',
                ],
            ]);

            $status = $response->getStatusCode();
            $this->testOk = $status >= 200 && $status < 300;
            $this->testMessage = $this->testOk
                ? "Conexión correcta (HTTP {$status})"
                : "Error HTTP {$status}";
        } catch (RequestException $exception) {
            $status = $exception->getResponse()?->getStatusCode();
            $reason = $exception->getResponse()?->getReasonPhrase() ?: $exception->getMessage();
            $this->testMessage = $status !== null
                ? "Error HTTP {$status} — {$reason}"
                : "Error de red: {$reason}";
        } catch (\Throwable $exception) {
            $this->testMessage = 'Error: '.$exception->getMessage();
        }

        $this->lastTestAt = now()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        session([
            'health_botmaker_ok' => $this->testOk,
            'health_botmaker_at' => $this->lastTestAt,
        ]);
    }

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
            throw new \RuntimeException("{$label}: JSON no válido.");
        }

        return $decoded;
    }

    public function render(): View
    {
        return view('livewire.botmaker-settings')
            ->layout('layouts.app', ['title' => 'Conexión Botmaker']);
    }
}
