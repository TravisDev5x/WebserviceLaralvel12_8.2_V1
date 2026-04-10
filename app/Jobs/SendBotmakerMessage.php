<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\FailedWebhook;
use App\Models\WebhookLog;
use App\Services\Bitrix24ConnectorService;
use App\Services\BotmakerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBotmakerMessage implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 300, 900, 3600];

    public int $timeout = 30;

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
        public readonly string $correlationId,
        public readonly int $webhookLogId,
        public readonly ?string $bitrixImChatId = null,
        public readonly ?string $bitrixMessageId = null,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(
        BotmakerService $botmakerService,
        Bitrix24ConnectorService $connectorService,
    ): void {
        $webhookLog = WebhookLog::query()->find($this->webhookLogId);
        $startedAt = microtime(true);

        if ($webhookLog instanceof WebhookLog) {
            $webhookLog->markAsProcessing();
        }

        try {
            $result = $botmakerService->sendMessage($this->phone, $this->message);

            $processingMs = (int) round((microtime(true) - $startedAt) * 1000);

            if ($webhookLog instanceof WebhookLog) {
                $webhookLog->update([
                    'status' => WebhookLog::STATUS_SENT,
                    'payload_out' => $result,
                    'http_status' => $result['http_status'],
                    'response_body' => $result['body'],
                    'processing_ms' => $processingMs,
                ]);
            }

            Log::channel('webhook')->info('SendBotmakerMessage: delivered to WhatsApp', [
                'phone' => $this->phone,
                'http_status' => $result['http_status'],
                'correlation_id' => $this->correlationId,
            ]);

            $this->confirmDeliveryToBitrix24($connectorService);
        } catch (Throwable $exception) {
            $processingMs = (int) round((microtime(true) - $startedAt) * 1000);

            if ($webhookLog instanceof WebhookLog) {
                $webhookLog->update([
                    'status' => WebhookLog::STATUS_FAILED,
                    'error_message' => $exception->getMessage(),
                    'error_type' => WebhookLog::ERROR_UNKNOWN,
                    'processing_ms' => $processingMs,
                ]);
            }

            Log::channel('webhook')->error('SendBotmakerMessage: failed', [
                'phone' => $this->phone,
                'error' => $exception->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $webhookLog = WebhookLog::query()->find($this->webhookLogId);
        if (! $webhookLog instanceof WebhookLog) {
            return;
        }

        FailedWebhook::createFromLog(
            $webhookLog,
            [
                'phone' => $this->phone,
                'message' => $this->message,
                'correlation_id' => $this->correlationId,
                'bitrix_im_chat_id' => $this->bitrixImChatId,
                'bitrix_message_id' => $this->bitrixMessageId,
            ],
            'botmaker_send_message',
            $exception->getMessage(),
            (int) ($webhookLog->http_status ?? 0),
        );
    }

    private function confirmDeliveryToBitrix24(Bitrix24ConnectorService $connectorService): void
    {
        if ($this->bitrixImChatId === null || $this->bitrixImChatId === ''
            || $this->bitrixMessageId === null || $this->bitrixMessageId === '') {
            Log::channel('webhook')->debug('Skipping Bitrix24 delivery status (no im chat/message id)', [
                'phone' => $this->phone,
                'correlation_id' => $this->correlationId,
            ]);

            return;
        }

        try {
            $lineId = (string) config_dynamic('bitrix24.line_id', config('services.bitrix24.line_id', '1'));

            $connectorService->sendDeliveryStatus($lineId, [
                'MESSAGES' => [
                    [
                        'im' => [
                            'chat_id' => (int) $this->bitrixImChatId,
                            'message_id' => (int) $this->bitrixMessageId,
                        ],
                        'message' => [
                            'id' => [$this->correlationId],
                            'date' => now()->timestamp,
                        ],
                        'chat' => [
                            'id' => $this->phone,
                        ],
                    ],
                ],
            ]);

            Log::channel('webhook')->debug('Delivery status confirmed to Bitrix24', [
                'phone' => $this->phone,
                'correlation_id' => $this->correlationId,
                'bitrix_im_chat_id' => $this->bitrixImChatId,
                'bitrix_message_id' => $this->bitrixMessageId,
            ]);
        } catch (Throwable $e) {
            Log::channel('webhook')->warning('Failed to confirm delivery to Bitrix24 (non-fatal)', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
        }
    }

}
