<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\WebhookDirection;
use App\Models\FailedWebhook;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedWebhooks implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $failedWebhooks = FailedWebhook::query()
            ->readyForRetry()
            ->with('webhookLog')
            ->get();

        foreach ($failedWebhooks as $failedWebhook) {
            if ($failedWebhook->isExhausted()) {
                continue;
            }

            if ($failedWebhook->webhookLog === null) {
                continue;
            }

            $direction = (string) $failedWebhook->direction;

            if ($direction === WebhookDirection::BotmakerToBitrix->value) {
                ProcessBotmakerPayload::dispatch($failedWebhook->webhookLog)->onQueue('webhooks');
            } elseif ($direction === WebhookDirection::BitrixToBotmaker->value) {
                ProcessBitrix24Payload::dispatch($failedWebhook->webhookLog)->onQueue('webhooks');
            } else {
                Log::channel('webhook')->warning('Dirección de webhook desconocida al reintentar', [
                    'failed_webhook_id' => $failedWebhook->id,
                    'direction' => $direction,
                ]);
            }
        }
    }
}
