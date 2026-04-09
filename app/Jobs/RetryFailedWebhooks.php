<?php

declare(strict_types=1);

namespace App\Jobs;

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

            if ($direction === 'botmaker_to_bitrix') {
                ProcessBotmakerPayload::dispatch($failedWebhook->webhookLog->id)->onQueue('webhooks');
            } elseif ($direction === 'bitrix_to_botmaker') {
                $payload = is_array($failedWebhook->payload) ? $failedWebhook->payload : [];
                $phone = (string) ($payload['phone'] ?? '');
                $message = (string) ($payload['message'] ?? '');
                $correlationId = (string) ($failedWebhook->webhookLog->correlation_id ?? '');

                if ($phone !== '' && $message !== '') {
                    SendBotmakerMessage::dispatch(
                        $phone,
                        $message,
                        $correlationId,
                        $failedWebhook->webhookLog->id,
                    )->onQueue('webhooks');
                } else {
                    Log::channel('webhook')->warning('Retry bitrix_to_botmaker: missing phone or message', [
                        'failed_webhook_id' => $failedWebhook->id,
                    ]);
                }
            } else {
                Log::channel('webhook')->warning('Dirección de webhook desconocida al reintentar', [
                    'failed_webhook_id' => $failedWebhook->id,
                    'direction' => $direction,
                ]);
            }
        }
    }
}
