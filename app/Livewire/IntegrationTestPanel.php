<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use App\Services\Bitrix24Service;
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
    public ?array $bitrixResult = null;

    /** @var array<string, mixed>|null */
    public ?array $connectivityResult = null;

    /** @var array<string, mixed>|null */
    public ?array $summary = null;

    public string $testPhone = '';

    public string $testMessageText = '';

    public ?string $sendTestResult = null;

    public string $bitrixLeadFirstName = 'Prueba';

    public string $bitrixLeadPhone = '+5255512345678';

    public string $bitrixLeadTitle = '';

    public ?string $createLeadResult = null;

    /** @var list<array{at:string,ok:bool,text:string}> */
    public array $botHistory = [];

    /** @var list<array{at:string,ok:bool,text:string}> */
    public array $bitrixHistory = [];

    /** @var list<string> */
    public array $flowSteps = [];

    public function mount(IntegrationProbeService $probe): void
    {
        $this->summary = $probe->webhookSummaryToday();
        $this->botHistory = session()->get('integration_test_bot_history', []);
        $this->bitrixHistory = session()->get('integration_test_bitrix_history', []);
        $suffix = (string) now()->format('His');
        $this->bitrixLeadTitle = "Lead prueba panel {$suffix}";
    }

    public function refreshSummary(IntegrationProbeService $probe): void
    {
        $this->summary = $probe->webhookSummaryToday();
    }

    public function runBitrixSampleLead(IntegrationProbeService $probe): void
    {
        try {
            $this->bitrixResult = $probe->runBitrixSampleLead('panel_web');
            $this->pushBitrixHistory($this->bitrixResult['success'] ?? false, 'Lead de prueba: '.($this->bitrixResult['body'] ?? ''));
        } catch (Throwable $e) {
            $this->bitrixResult = [
                'success' => false,
                'http_status' => 0,
                'lead_id' => null,
                'body' => $e->getMessage(),
                'fields' => [],
                'base_url' => '',
                'config_warning' => 'Excepción al llamar a Bitrix24.',
            ];
            $this->pushBitrixHistory(false, $e->getMessage());
        }

        try {
            $this->summary = $probe->webhookSummaryToday();
        } catch (Throwable) {
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
        }
    }

    public function sendTestWhatsApp(BotmakerService $botmaker): void
    {
        $this->sendTestResult = null;
        $this->validate([
            'testPhone' => ['required', 'string', 'max:30'],
            'testMessageText' => ['required', 'string', 'max:500'],
        ], [], [
            'testPhone' => 'Número destino',
            'testMessageText' => 'Mensaje',
        ]);

        $response = $botmaker->sendMessage($this->testPhone, $this->testMessageText);
        $ok = $response['success'];
        $this->sendTestResult = ($ok ? 'Éxito' : 'Error')." — HTTP {$response['http_status']}: ".substr((string) $response['body'], 0, 400);
        $this->pushBotHistory($ok, $this->sendTestResult);
    }

    public function createTestLead(Bitrix24Service $bitrix): void
    {
        $this->createLeadResult = null;
        $this->validate([
            'bitrixLeadFirstName' => ['required', 'string', 'max:120'],
            'bitrixLeadPhone' => ['required', 'string', 'max:40'],
            'bitrixLeadTitle' => ['required', 'string', 'max:200'],
        ], [], [
            'bitrixLeadFirstName' => 'Nombre',
            'bitrixLeadPhone' => 'Teléfono',
            'bitrixLeadTitle' => 'Título',
        ]);

        $fields = [
            'TITLE' => $this->bitrixLeadTitle,
            'NAME' => $this->bitrixLeadFirstName,
            'PHONE' => [['VALUE' => $this->bitrixLeadPhone, 'VALUE_TYPE' => 'WORK']],
        ];

        $response = $bitrix->createLead($fields);
        $ok = $response['success'];
        $id = null;
        $decoded = json_decode((string) $response['body'], true);
        if (is_array($decoded) && isset($decoded['result']) && is_numeric($decoded['result'])) {
            $id = (int) $decoded['result'];
        }
        $this->createLeadResult = ($ok ? 'Lead creado' : 'Error').($id ? " — ID {$id}" : '')." — HTTP {$response['http_status']}";
        $this->pushBitrixHistory($ok, $this->createLeadResult);
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

        $this->flowSteps[] = '1. Enviando POST a /api/webhook/botmaker…';
        try {
            $response = Http::asJson()
                ->withHeaders(['X-Botmaker-Signature' => $secret])
                ->timeout(30)
                ->post(url('/api/webhook/botmaker'), $payload);
            $this->flowSteps[] = '2. Respuesta HTTP '.$response->status().': '.substr($response->body(), 0, 200);
        } catch (Throwable $e) {
            $this->flowSteps[] = '2. Error: '.$e->getMessage();
        }
    }

    public function simulateFlowBitrixToBotmaker(): void
    {
        $this->flowSteps = [];
        $this->flowSteps[] = 'El flujo Bitrix24 -> Middleware fue deshabilitado en esta versión.';
        $this->flowSteps[] = 'Flujo activo: Botmaker -> Middleware -> Bitrix24.';
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

    private function resolveBitrixApplicationToken(): string
    {
        if (Schema::hasTable('authorized_tokens')) {
            $row = AuthorizedToken::query()->active()->platform('bitrix24')->outgoing()->where('token', '!=', '')->orderBy('id')->first(['token']);
            if ($row !== null && trim((string) $row->token) !== '') {
                return (string) $row->token;
            }
        }

        return trim((string) config_dynamic('bitrix24.webhook_secret', config('services.bitrix24.webhook_secret', '')));
    }

    private function pushBotHistory(bool $ok, string $text): void
    {
        $list = session()->get('integration_test_bot_history', []);
        $list[] = ['at' => now()->format('Y-m-d H:i:s'), 'ok' => $ok, 'text' => $text];
        $list = array_slice($list, -5);
        session()->put('integration_test_bot_history', $list);
        $this->botHistory = $list;
    }

    private function pushBitrixHistory(bool $ok, string $text): void
    {
        $list = session()->get('integration_test_bitrix_history', []);
        $list[] = ['at' => now()->format('Y-m-d H:i:s'), 'ok' => $ok, 'text' => $text];
        $list = array_slice($list, -5);
        session()->put('integration_test_bitrix_history', $list);
        $this->bitrixHistory = $list;
    }

    public function render(): View
    {
        $botHistoryView = collect($this->botHistory)
            ->reverse()
            ->values()
            ->map(function (array $item): array {
                $item['text_short'] = Str::limit((string) ($item['text'] ?? ''), 80);
                return $item;
            })
            ->all();

        $bitrixHistoryView = collect($this->bitrixHistory)
            ->reverse()
            ->values()
            ->map(function (array $item): array {
                $item['text_short'] = Str::limit((string) ($item['text'] ?? ''), 80);
                return $item;
            })
            ->all();

        return view('livewire.integration-test-panel', [
            'botHistoryView' => $botHistoryView,
            'bitrixHistoryView' => $bitrixHistoryView,
        ])
            ->layout('layouts.app', [
                'title' => 'Pruebas de integración',
            ]);
    }
}
