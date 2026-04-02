<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\WebhookLog;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class WebhookLogDetail extends Component
{
    public int $webhookLogId;

    public function mount(int $webhookLogId): void
    {
        $this->webhookLogId = $webhookLogId;
    }

    public function render(): View
    {
        $webhookLog = WebhookLog::query()
            ->with('failedWebhook')
            ->findOrFail($this->webhookLogId);

        return view('livewire.webhook-log-detail', [
            'webhookLog' => $webhookLog,
            'payloadInJson' => $this->formatJson($webhookLog->payload_in),
            'payloadOutJson' => $this->formatJson($webhookLog->payload_out),
        ])->layout('layouts.app', [
            'title' => 'Detalle de Webhook #'.$this->webhookLogId,
        ]);
    }

    /**
     * @param mixed $payload
     */
    public function formatJson(mixed $payload): string
    {
        if ($payload === null) {
            return '{}';
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
            }

            return $payload;
        }

        if (is_array($payload)) {
            return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
        }

        return (string) $payload;
    }
}
