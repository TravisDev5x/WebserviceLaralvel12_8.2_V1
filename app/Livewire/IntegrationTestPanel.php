<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use App\Services\Bitrix24ConnectorService;
use App\Services\BotmakerService;
use App\Services\IntegrationProbeService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;
use Throwable;

class IntegrationTestPanel extends Component
{
    /** @var array<string, mixed>|null */
    public ?array $connectivityResult = null;

    /** @var array<string, mixed>|null */
    public ?array $summary = null;

    // v2 Channel message test
    public string $testPhone = '+5255512345678';

    public string $testName = 'Prueba Panel';

    public string $testMessageText = '';

    public ?string $channelResult = null;

    public bool $channelOk = false;

    // v2 Botmaker send test
    public string $botmakerTestPhone = '+5255512345678';

    public string $botmakerTestText = '';

    public ?string $botmakerSendResult = null;

    public bool $botmakerSendOk = false;

    /** @var list<array{at:string,ok:bool,text:string}> */
    public array $testHistory = [];

    /** @var list<string> */
    public array $flowSteps = [];

    public function mount(IntegrationProbeService $probe): void
    {
        $this->summary = $probe->webhookSummaryToday();
        $this->testHistory = session()->get('integration_test_history', []);
        $suffix = (string) now()->format('His');
        $this->testMessageText = "Mensaje de prueba desde panel ({$suffix})";
        $this->botmakerTestText = "Mensaje de prueba panel → Botmaker ({$suffix})";
    }

    public function refreshSummary(IntegrationProbeService $probe): void
    {
        $this->summary = $probe->webhookSummaryToday();
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
        }
    }

    /**
     * v2: Sends a test message to the Bitrix24 Open Channel via imconnector.send.messages.
     */
    public function sendTestChannelMessage(): void
    {
        $this->channelResult = null;
        $this->channelOk = false;

        $this->validate([
            'testPhone' => ['required', 'string', 'max:40'],
            'testName' => ['required', 'string', 'max:120'],
            'testMessageText' => ['required', 'string', 'max:500'],
        ], [], [
            'testPhone' => 'Teléfono',
            'testName' => 'Nombre',
            'testMessageText' => 'Mensaje',
        ]);

        try {
            $connector = app(Bitrix24ConnectorService::class);
            $result = $connector->sendSingleMessage($this->testPhone, $this->testName, $this->testMessageText);

            $this->channelOk = (bool) ($result['success'] ?? false);
            $this->channelResult = $this->channelOk
                ? 'Mensaje enviado al Canal Abierto — HTTP ' . ($result['http_status'] ?? '?')
                : 'Error — HTTP ' . ($result['http_status'] ?? '?');
        } catch (Throwable $e) {
            $this->channelResult = 'Error: ' . $e->getMessage();
        }

        $this->pushHistory($this->channelOk, 'Canal Abierto: ' . ($this->channelResult ?? ''));
    }

    /**
     * v2: Tests sending a message via BotmakerService::sendMessage() (Flow B direction).
     */
    public function sendTestBotmakerMessage(): void
    {
        $this->botmakerSendResult = null;
        $this->botmakerSendOk = false;

        $this->validate([
            'botmakerTestPhone' => ['required', 'string', 'max:40'],
            'botmakerTestText' => ['required', 'string', 'max:500'],
        ], [], [
            'botmakerTestPhone' => 'Teléfono',
            'botmakerTestText' => 'Mensaje',
        ]);

        try {
            $botmaker = app(BotmakerService::class);
            $result = $botmaker->sendMessage($this->botmakerTestPhone, $this->botmakerTestText);

            $this->botmakerSendOk = true;
            $this->botmakerSendResult = 'Mensaje enviado a Botmaker — HTTP ' . ($result['http_status'] ?? '?');
        } catch (Throwable $e) {
            $this->botmakerSendResult = 'Error: ' . $e->getMessage();
        }

        $this->pushHistory($this->botmakerSendOk, 'Botmaker send: ' . ($this->botmakerSendResult ?? ''));
    }

    public function simulateFlowBotmakerToBitrix(): void
    {
        $this->flowSteps = [];
        $secret = $this->resolveBotmakerWebhookSecret();
        if ($secret === '') {
            $this->flowSteps[] = 'Error: no hay secreto de webhook Botmaker (tokens salientes o configuración).';

            return;
        }

        $payload = [
            'event' => 'simulacion_flujo_a',
            'whatsappNumber' => '+5255512345678',
            'message' => ['text' => 'Mensaje de simulación desde el panel'],
        ];

        $this->flowSteps[] = '1. Enviando POST a /api/webhook/botmaker...';
        try {
            $response = Http::asJson()
                ->withHeaders(['X-Botmaker-Signature' => $secret])
                ->timeout(30)
                ->post(url('/api/webhook/botmaker'), $payload);
            $this->flowSteps[] = '2. Respuesta HTTP ' . $response->status() . ': ' . substr($response->body(), 0, 200);
        } catch (Throwable $e) {
            $this->flowSteps[] = '2. Error: ' . $e->getMessage();
        }
    }

    private function resolveBotmakerWebhookSecret(): string
    {
        if (Schema::hasTable('authorized_tokens')) {
            $row = AuthorizedToken::query()->active()->platform('botmaker')->outgoing()->where('token', '!=', '')->orderBy('id')->first(['token']);
            if ($row !== null && trim((string) $row->token) !== '') {
                return (string) $row->token;
            }
        }

        return trim((string) config_dynamic('botmaker.webhook_secret', config('services.botmaker.webhook_secret', '')));
    }

    private function pushHistory(bool $ok, string $text): void
    {
        $list = session()->get('integration_test_history', []);
        $list[] = ['at' => now()->format('Y-m-d H:i:s'), 'ok' => $ok, 'text' => $text];
        $list = array_slice($list, -10);
        session()->put('integration_test_history', $list);
        $this->testHistory = $list;
    }

    public function render(): View
    {
        $historyView = collect($this->testHistory)
            ->reverse()
            ->values()
            ->map(function (array $item): array {
                $item['text_short'] = Str::limit((string) ($item['text'] ?? ''), 100);

                return $item;
            })
            ->all();

        return view('livewire.integration-test-panel', [
            'historyView' => $historyView,
        ])
            ->layout('layouts.app', [
                'title' => 'Pruebas de integración',
            ]);
    }
}
