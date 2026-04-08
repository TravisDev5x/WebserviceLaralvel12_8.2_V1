<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use App\Models\FailedWebhook;
use App\Models\WebhookLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Throwable;

class HealthStatus extends Component
{
    public function render(): View
    {
        $dbOk = true;
        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            $dbOk = false;
        }

        $queueStuck = 0;
        $pendingRetries = 0;
        $lastWebhookAt = null;
        $botmakerOk = false;
        $bitrixOk = false;

        try {
            if (Schema::hasTable('webhook_logs')) {
                $queueStuck = (int) WebhookLog::query()
                    ->whereIn('status', ['received', 'processing'])
                    ->where('created_at', '<', now()->subMinutes(10))
                    ->count();

                $lastWebhookAt = optional(WebhookLog::query()->latest()->first()?->created_at)?->format('Y-m-d H:i:s');
            }

            if (Schema::hasTable('failed_webhooks')) {
                $pendingRetries = (int) FailedWebhook::query()->pending()->count();
            }

            $bmUrl = AuthorizedToken::resolvedBotmakerApiUrl();
            $bmToken = AuthorizedToken::resolvedBotmakerApiToken();
            $botmakerOk = $bmUrl !== '' && $bmToken !== '';

            $bxUrl = AuthorizedToken::resolvedBitrix24WebhookUrl();
            $bitrixOk = $bxUrl !== '';
        } catch (Throwable) {
            // Valores por defecto arriba; no tumbar /monitor por un fallo puntual de consulta.
        }

        return view('livewire.health-status', [
            'dbOk' => $dbOk,
            'botmakerOk' => $botmakerOk,
            'bitrixOk' => $bitrixOk,
            'queueStuck' => $queueStuck,
            'pendingRetries' => $pendingRetries,
            'lastWebhookAt' => $lastWebhookAt,
        ]);
    }
}
