<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use App\Models\FailedWebhook;
use App\Models\WebhookLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
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

        $bmUrl = AuthorizedToken::resolvedBotmakerApiUrl();
        $bmToken = AuthorizedToken::resolvedBotmakerApiToken();
        $botmakerOk = $bmUrl !== '' && $bmToken !== '';

        $bxUrl = AuthorizedToken::resolvedBitrix24WebhookUrl();
        $bitrixOk = $bxUrl !== '';

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
