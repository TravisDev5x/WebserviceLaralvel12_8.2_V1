<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\FailedWebhook;
use App\Models\WebhookLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class HealthStatus extends Component
{
    public function render(): View
    {
        $dbOk = true;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $dbOk = false;
        }

        $queueStuck = WebhookLog::query()
            ->whereIn('status', ['received', 'processing'])
            ->where('created_at', '<', now()->subMinutes(10))
            ->count();

        $bmUrl = trim((string) config_dynamic('botmaker.api_url', config('services.botmaker.api_url', '')));
        $bmToken = trim((string) config_dynamic('botmaker.api_token', config('services.botmaker.api_token', '')));
        $bmDbToken = \App\Models\AuthorizedToken::getPrimaryBotmakerApiToken();
        $botmakerOk = $bmUrl !== '' && ($bmToken !== '' || (is_string($bmDbToken) && $bmDbToken !== ''));

        $bxSetting = trim((string) config_dynamic('bitrix24.webhook_url', config('services.bitrix24.webhook_url', '')));
        $bxDb = Schema::hasTable('authorized_tokens') ? \App\Models\AuthorizedToken::getWebhookUrl('bitrix24') : null;
        $bitrixOk = (is_string($bxDb) && $bxDb !== '') || $bxSetting !== '';

        return view('livewire.health-status', [
            'dbOk' => $dbOk,
            'botmakerOk' => $botmakerOk,
            'bitrixOk' => $bitrixOk,
            'queueStuck' => $queueStuck,
            'pendingRetries' => FailedWebhook::query()->pending()->count(),
            'lastWebhookAt' => optional(WebhookLog::query()->latest()->first()?->created_at)?->format('Y-m-d H:i:s'),
        ]);
    }
}
