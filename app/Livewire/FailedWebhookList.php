<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\WebhookDirection;
use App\Jobs\ProcessBitrix24Payload;
use App\Jobs\ProcessBotmakerPayload;
use App\Models\FailedWebhook;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
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

        return view('livewire.failed-webhook-list', [
            'failedWebhooks' => $failedWebhooks,
            'statuses' => [
                'all' => 'Todos',
                FailedWebhook::STATUS_PENDING => 'Pendiente',
                FailedWebhook::STATUS_RETRYING => 'Reintentando',
                FailedWebhook::STATUS_RESOLVED => 'Resuelto',
                FailedWebhook::STATUS_EXHAUSTED => 'Agotado',
            ],
            'canManageFailed' => user_can('failed.manage'),
        ])->layout('layouts.app', [
            'title' => 'Webhooks Fallidos',
        ]);
    }

    public function failedWebhookLeadId(FailedWebhook $failedWebhook): string
    {
        $payload = is_array($failedWebhook->payload) ? $failedWebhook->payload : [];
        $value = $failedWebhook->webhookLog?->external_id ?? ($payload['data']['FIELDS']['ID'] ?? '-');

        return is_scalar($value) ? (string) $value : '-';
    }

    public function failedWebhookLastErrorShort(FailedWebhook $failedWebhook): string
    {
        if ($failedWebhook->last_error === null || $failedWebhook->last_error === '') {
            return '-';
        }

        return Str::limit((string) $failedWebhook->last_error, 90);
    }

    public function failedWebhookFriendlyError(FailedWebhook $failedWebhook): string
    {
        $lastError = $this->failedWebhookLastErrorShort($failedWebhook);
        $errorText = strtolower((string) $failedWebhook->last_error);

        if (str_contains($errorText, '401')) {
            return 'Token rechazado — verificar permisos en Botmaker';
        }
        if (str_contains($errorText, 'timed out')) {
            return 'No se pudo conectar — el servicio puede estar caído';
        }
        if (str_contains($errorText, '500')) {
            return 'Error interno del servicio destino';
        }

        return $lastError;
    }

    public function failedWebhookRowCanRetry(FailedWebhook $failedWebhook): bool
    {
        return in_array($failedWebhook->status, [FailedWebhook::STATUS_PENDING, FailedWebhook::STATUS_EXHAUSTED], true);
    }

    public function failedWebhookProgressPercent(FailedWebhook $failedWebhook): int
    {
        if ($failedWebhook->max_attempts <= 0) {
            return 0;
        }

        return min(100, (int) (($failedWebhook->attempts / $failedWebhook->max_attempts) * 100));
    }

    public function failedWebhookStatusStyle(FailedWebhook $failedWebhook): string
    {
        return match ($failedWebhook->status) {
            FailedWebhook::STATUS_RESOLVED => 'background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 999px;',
            FailedWebhook::STATUS_RETRYING => 'background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 999px;',
            FailedWebhook::STATUS_EXHAUSTED => 'background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 999px;',
            default => 'background: #dbeafe; color: #1e3a8a; padding: 2px 8px; border-radius: 999px;',
        };
    }
}
