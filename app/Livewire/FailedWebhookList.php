<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\WebhookDirection;
use App\Jobs\ProcessBitrix24Payload;
use App\Jobs\ProcessBotmakerPayload;
use App\Models\FailedWebhook;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class FailedWebhookList extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function forceRetry(int $id): void
    {
        if (! user_can('failed.manage')) {
            return;
        }

        $failedWebhook = FailedWebhook::query()
            ->with('webhookLog')
            ->findOrFail($id);

        if (! in_array($failedWebhook->status, [FailedWebhook::STATUS_PENDING, FailedWebhook::STATUS_EXHAUSTED], true)) {
            return;
        }

        if ($failedWebhook->webhookLog === null) {
            return;
        }

        if ($failedWebhook->direction === WebhookDirection::BotmakerToBitrix->value) {
            ProcessBotmakerPayload::dispatch($failedWebhook->webhookLog)->onQueue('webhooks');
        }

        if ($failedWebhook->direction === WebhookDirection::BitrixToBotmaker->value) {
            ProcessBitrix24Payload::dispatch($failedWebhook->webhookLog)->onQueue('webhooks');
        }

        $failedWebhook->update([
            'status' => FailedWebhook::STATUS_RETRYING,
            'next_retry_at' => null,
        ]);
    }

    public function markResolved(int $id): void
    {
        if (! user_can('failed.manage')) {
            return;
        }

        $failedWebhook = FailedWebhook::query()->findOrFail($id);
        $failedWebhook->markAsResolved();
    }

    public function retryAllPending(): void
    {
        if (! user_can('failed.manage')) {
            return;
        }

        $rows = FailedWebhook::query()
            ->with('webhookLog')
            ->whereIn('status', [FailedWebhook::STATUS_PENDING, FailedWebhook::STATUS_EXHAUSTED])
            ->limit(50)
            ->get();

        foreach ($rows as $failedWebhook) {
            if ($failedWebhook->webhookLog === null) {
                continue;
            }

            if ($failedWebhook->direction === WebhookDirection::BotmakerToBitrix->value) {
                ProcessBotmakerPayload::dispatch($failedWebhook->webhookLog)->onQueue('webhooks');
            }

            if ($failedWebhook->direction === WebhookDirection::BitrixToBotmaker->value) {
                ProcessBitrix24Payload::dispatch($failedWebhook->webhookLog)->onQueue('webhooks');
            }

            $failedWebhook->update([
                'status' => FailedWebhook::STATUS_RETRYING,
                'next_retry_at' => null,
            ]);
        }
    }

    public function render(): View
    {
        $failedWebhooks = FailedWebhook::query()
            ->with('webhookLog')
            ->when($this->statusFilter !== 'all', function (Builder $query): void {
                $query->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.failed-webhook-list-safe', [
            'failedWebhooks' => $failedWebhooks,
            'statuses' => [
                'all' => 'Todos',
                FailedWebhook::STATUS_PENDING => 'Pendiente',
                FailedWebhook::STATUS_RETRYING => 'Reintentando',
                FailedWebhook::STATUS_RESOLVED => 'Resuelto',
                FailedWebhook::STATUS_EXHAUSTED => 'Agotado',
            ],
        ])->layout('layouts.app', [
            'title' => 'Webhooks Fallidos',
        ]);
    }
}
