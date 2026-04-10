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
            $dispatched = false;

            if ($direction === 'botmaker_to_bitrix') {
                ProcessBotmakerPayload::dispatch($failedWebhook->webhookLog->id)->onQueue('webhooks');
                $dispatched = true;
            } elseif ($direction === 'bitrix_to_botmaker') {
                $payload = is_array($failedWebhook->payload) ? $failedWebhook->payload : [];
                $phone = (string) ($payload['phone'] ?? '');
                $message = (string) ($payload['message'] ?? '');
                $correlationId = (string) ($payload['correlation_id'] ?? $failedWebhook->webhookLog->correlation_id ?? '');

                if ($phone !== '' && $message !== '') {
                    $bitrixImChatId = isset($payload['bitrix_im_chat_id'])
                        ? (string) $payload['bitrix_im_chat_id'] : null;
                    $bitrixMessageId = isset($payload['bitrix_message_id'])
                        ? (string) $payload['bitrix_message_id'] : null;

                    SendBotmakerMessage::dispatch(
                        $phone,
                        $message,
                        $correlationId,
                        $failedWebhook->webhookLog->id,
                        $bitrixImChatId !== '' ? $bitrixImChatId : null,
                        $bitrixMessageId !== '' ? $bitrixMessageId : null,
                    )->onQueue('webhooks');
                    $dispatched = true;
                } else {
                    Log::channel('webhook')->warning('Retry bitrix_to_botmaker: missing phone or message', [
                        'failed_webhook_id' => $failedWebhook->id,
                        'payload_keys' => array_keys($payload),
                    ]);
                }
            } else {
                Log::channel('webhook')->warning('Dirección de webhook desconocida al reintentar', [
                    'failed_webhook_id' => $failedWebhook->id,
                    'direction' => $direction,
                ]);
            }

            if ($dispatched) {
                $failedWebhook->update([
                    'status' => FailedWebhook::STATUS_RETRYING,
                    'next_retry_at' => null,
                ]);
            }
        }
    }
}
