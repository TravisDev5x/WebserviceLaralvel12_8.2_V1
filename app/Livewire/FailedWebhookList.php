<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\WebhookDirection;
use App\Jobs\ProcessBotmakerPayload;
use App\Models\FailedWebhook;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use stdClass;
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
            ->paginate(15)
            ->through(function (FailedWebhook $failedWebhook): stdClass {
                $payload = is_array($failedWebhook->payload) ? $failedWebhook->payload : [];
                $leadValue = $failedWebhook->webhookLog?->external_id ?? ($payload['data']['FIELDS']['ID'] ?? '-');
                $leadId = is_scalar($leadValue) ? (string) $leadValue : '-';
                $lastErrorRaw = (string) ($failedWebhook->last_error ?? '');
                $lastErrorShort = $lastErrorRaw === '' ? '-' : Str::limit($lastErrorRaw, 90);
                $errorText = strtolower($lastErrorRaw);
                $friendlyError = $lastErrorShort;
                if (str_contains($errorText, '401')) {
                    $friendlyError = 'Token rechazado — verificar permisos en Botmaker';
                } elseif (str_contains($errorText, 'timed out')) {
                    $friendlyError = 'No se pudo conectar — el servicio puede estar caído';
                } elseif (str_contains($errorText, '500')) {
                    $friendlyError = 'Error interno del servicio destino';
                }

                $statusClass = 'status-pending';
                if ($failedWebhook->status === FailedWebhook::STATUS_RESOLVED) {
                    $statusClass = 'status-resolved';
                } elseif ($failedWebhook->status === FailedWebhook::STATUS_RETRYING) {
                    $statusClass = 'status-retrying';
                } elseif ($failedWebhook->status === FailedWebhook::STATUS_EXHAUSTED) {
                    $statusClass = 'status-exhausted';
                }

                $canRetry = in_array($failedWebhook->status, [FailedWebhook::STATUS_PENDING, FailedWebhook::STATUS_EXHAUSTED], true);

                return (object) [
                    'id' => $failedWebhook->id,
                    'direction' => (string) $failedWebhook->direction,
                    'lead_id' => $leadId,
                    'attempts' => (int) $failedWebhook->attempts,
                    'max_attempts' => (int) $failedWebhook->max_attempts,
                    'status' => (string) $failedWebhook->status,
                    'status_class' => $statusClass,
                    'last_error' => $friendlyError,
                    'last_error_full' => $lastErrorRaw === '' ? '-' : $lastErrorRaw,
                    'next_retry_at' => $failedWebhook->next_retry_at?->format('Y-m-d H:i:s') ?? '-',
                    'created_at' => $failedWebhook->created_at?->format('Y-m-d H:i:s') ?? '-',
                    'can_retry' => $canRetry,
                ];
            });

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
}
