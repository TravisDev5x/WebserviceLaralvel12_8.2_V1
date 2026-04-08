<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use App\Models\WebhookLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class BotmakerSettings extends Component
{
    public string $webhookUrl = '';

    public bool $appUrlIsHttp = false;

    /** @var array<int, bool> */
    public array $visibleTokens = [];

    public ?string $testMessage = null;

    public bool $testOk = false;

    public ?string $lastWebhookAt = null;

    public int $totalToday = 0;

    public int $successToday = 0;

    public int $failedToday = 0;

    public function mount(): void
    {
        $baseUrl = rtrim((string) config('app.url', ''), '/');
        $this->webhookUrl = $baseUrl !== '' ? $baseUrl.'/api/webhook/botmaker' : '/api/webhook/botmaker';
        $this->appUrlIsHttp = str_starts_with(strtolower($baseUrl), 'http://');
        $this->refreshStats();
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
            // El payload de prueba no incluye contactId para evitar crear un lead real.
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
                $this->testMessage = 'Webhook recibido correctamente ✅';
            } else {
                $this->testMessage = 'Error: HTTP '.$response->status().' - '.$response->body();
            }
        } catch (\Throwable $exception) {
            $this->testMessage = 'Error: '.$exception->getMessage();
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

    public function render(): View
    {
        return view('livewire.botmaker-settings')
            ->layout('layouts.app', ['title' => 'Recepción de webhooks de Botmaker']);
    }
}
