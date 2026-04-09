<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use App\Models\Setting;
use App\Models\WebhookLog;
use App\Services\BotmakerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Throwable;

class BotmakerSettings extends Component
{
    public string $webhookUrl = '';

    public bool $appUrlIsHttp = false;

    // API config for sending messages (Flujo B)
    public string $apiUrl = '';

    public string $apiToken = '';

    public string $sendEndpoint = '';

    public bool $apiTokenVisible = false;

    public ?string $apiSaveMessage = null;

    public bool $apiSaveOk = false;

    public ?string $apiTestMessage = null;

    public bool $apiTestOk = false;

    /** @var array<int, bool> */
    public array $visibleTokens = [];

    public ?string $testMessage = null;

    public bool $testOk = false;

    public ?string $lastWebhookAt = null;

    public int $totalToday = 0;

    public int $successToday = 0;

    public int $failedToday = 0;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $baseUrl = rtrim((string) config('app.url', ''), '/');
        $this->webhookUrl = $baseUrl !== '' ? $baseUrl . '/api/webhook/botmaker' : '/api/webhook/botmaker';
        $this->appUrlIsHttp = str_starts_with(strtolower($baseUrl), 'http://');

        $this->apiUrl = (string) config_dynamic('botmaker.api_url', config('services.botmaker.api_url', 'https://go.botmaker.com/api/v1.0'));
        $this->apiToken = AuthorizedToken::resolvedBotmakerApiToken();
        $this->sendEndpoint = (string) config_dynamic('botmaker.send_endpoint', config('services.botmaker.send_endpoint', '/message/v2'));

        $this->refreshStats();
    }

    public function saveApiConfig(): void
    {
        $this->apiSaveMessage = null;
        $this->apiSaveOk = false;

        $validated = $this->validate([
            'apiUrl' => ['required', 'url', 'max:500'],
            'apiToken' => ['required', 'string', 'min:10', 'max:1000'],
            'sendEndpoint' => ['required', 'string', 'max:100'],
        ], [], [
            'apiUrl' => 'URL de API Botmaker',
            'apiToken' => 'Token de API Botmaker',
            'sendEndpoint' => 'Endpoint de envío',
        ]);

        try {
            Setting::set('botmaker.api_url', (string) $validated['apiUrl']);
            Setting::set('botmaker.api_token', (string) $validated['apiToken']);
            Setting::set('botmaker.send_endpoint', (string) $validated['sendEndpoint']);

            $this->apiSaveOk = true;
            $this->apiSaveMessage = 'Configuración de API Botmaker guardada correctamente.';
        } catch (Throwable $e) {
            $this->apiSaveMessage = 'Error al guardar: ' . $e->getMessage();
        }
    }

    public function testApiToken(): void
    {
        $this->apiTestMessage = null;
        $this->apiTestOk = false;

        $resolvedToken = AuthorizedToken::resolvedBotmakerApiToken();
        if ($resolvedToken === '') {
            $this->apiTestMessage = 'No hay API Token configurado. Guárdalo primero.';

            return;
        }

        $resolvedUrl = AuthorizedToken::resolvedBotmakerApiUrl();
        if ($resolvedUrl === '') {
            $this->apiTestMessage = 'No hay URL de API configurada. Guárdala primero.';

            return;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'access-token' => $resolvedToken,
                ])
                ->get($resolvedUrl);

            $status = $response->status();
            if ($status >= 200 && $status < 300) {
                $this->apiTestOk = true;
                $this->apiTestMessage = "API Botmaker OK (HTTP {$status}). El token es válido.";
            } elseif ($status === 401 || $status === 403) {
                $this->apiTestMessage = "Error HTTP {$status}: Token inválido o sin permisos. Verifica el token.";
            } else {
                $this->apiTestMessage = "Respuesta HTTP {$status}. Verifica la URL y el token.";
            }
        } catch (Throwable $e) {
            $this->apiTestMessage = 'Error de conexión: ' . $e->getMessage();
        }
    }

    public function toggleApiTokenVisibility(): void
    {
        $this->apiTokenVisible = ! $this->apiTokenVisible;
    }

    public function toggleTokenVisibility(int $tokenId): void
    {
        if (isset($this->visibleTokens[$tokenId]) && $this->visibleTokens[$tokenId] === true) {
            unset($this->visibleTokens[$tokenId]);
        } else {
            $this->visibleTokens[$tokenId] = true;
        }
    }

    public function sendTestWebhook(): void
    {
        $this->testMessage = null;
        $this->testOk = false;

        if (! Schema::hasTable('authorized_tokens')) {
            $this->testMessage = 'Error: no existe la tabla authorized_tokens.';

            return;
        }

        $token = (string) (AuthorizedToken::query()
            ->where('platform', 'botmaker')
            ->where('direction', AuthorizedToken::DIRECTION_OUTGOING)
            ->where('is_active', true)
            ->where('token', '!=', '')
            ->orderBy('id')
            ->value('token') ?? '');

        if ($token === '') {
            $this->testMessage = 'Error: no hay tokens activos de Botmaker para firmar la prueba.';

            return;
        }

        try {
            $payload = [
                'firstName' => 'Prueba',
                'lastName' => 'Webhook',
                'messages' => [
                    ['message' => 'Mensaje de prueba desde monitor'],
                ],
                'event' => 'test_webhook',
                'is_test' => true,
            ];

            $response = Http::timeout(10)
                ->acceptJson()
                ->withHeaders(['auth-bm-token' => $token])
                ->post($this->webhookUrl, $payload);

            if (in_array($response->status(), [200, 202, 422], true)) {
                $this->testOk = true;
                $this->testMessage = 'Webhook recibido correctamente';
            } else {
                $this->testMessage = 'Error: HTTP ' . $response->status() . ' - ' . $response->body();
            }
        } catch (Throwable $exception) {
            $this->testMessage = 'Error: ' . $exception->getMessage();
        }

        $this->refreshStats();
    }

    private function refreshStats(): void
    {
        if (! Schema::hasTable('webhook_logs')) {
            $this->lastWebhookAt = null;
            $this->totalToday = 0;
            $this->successToday = 0;
            $this->failedToday = 0;

            return;
        }

        $baseQuery = WebhookLog::query()
            ->where('direction', WebhookLog::DIRECTION_BOTMAKER_TO_BITRIX);

        $lastWebhook = (clone $baseQuery)->latest('created_at')->first();
        $this->lastWebhookAt = $lastWebhook?->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s');

        $todayQuery = (clone $baseQuery)->whereDate('created_at', now()->toDateString());
        $this->totalToday = (clone $todayQuery)->count();
        $this->successToday = (clone $todayQuery)->where('status', WebhookLog::STATUS_SENT)->count();
        $this->failedToday = (clone $todayQuery)->where('status', WebhookLog::STATUS_FAILED)->count();
    }

    public function getBotmakerTokensProperty()
    {
        if (! Schema::hasTable('authorized_tokens')) {
            return collect();
        }

        return AuthorizedToken::query()
            ->where('platform', 'botmaker')
            ->where('direction', AuthorizedToken::DIRECTION_OUTGOING)
            ->orderBy('is_active', 'desc')
            ->orderBy('id')
            ->get(['id', 'label', 'token', 'is_active', 'last_used_at']);
    }

    public function getResolvedTokenSourceProperty(): string
    {
        $fromSetting = trim((string) Setting::get('botmaker.api_token', ''));
        if ($fromSetting !== '') {
            return 'Panel (settings)';
        }

        $fromRow = AuthorizedToken::getPrimaryBotmakerApiToken();
        if (is_string($fromRow) && trim($fromRow) !== '') {
            return 'AuthorizedTokens (DB)';
        }

        $fromEnv = trim((string) config('services.botmaker.api_token', ''));
        if ($fromEnv !== '') {
            return '.env';
        }

        return 'No configurado';
    }

    public function render(): View
    {
        return view('livewire.botmaker-settings')
            ->layout('layouts.app', ['title' => 'Conexión Botmaker']);
    }
}
