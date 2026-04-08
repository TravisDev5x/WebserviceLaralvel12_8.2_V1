<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\WebhookDirection;
use App\Enums\WebhookStatus;
use App\Models\FailedWebhook;
use App\Models\WebhookLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookDashboard extends Component
{
    use WithPagination;

    public function render(): View
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $totalToday = WebhookLog::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        $successToday = WebhookLog::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('status', WebhookStatus::Sent->value)
            ->count();

        $failedToday = WebhookLog::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('status', WebhookStatus::Failed->value)
            ->count();

        $pendingQueue = FailedWebhook::query()
            ->pending()
            ->count();

        $latestWebhooks = WebhookLog::query()
            ->latest()
            ->paginate(10, ['*'], 'dashboard_page')
            ->through(function (WebhookLog $log): array {
                return [
                    'id' => $log->id,
                    'direction' => $this->directionLabel((string) $log->direction),
                    'direction_icon' => $this->directionIcon((string) $log->direction),
                    'source_event' => (string) $log->source_event,
                    'status' => (string) $log->status,
                    'status_label' => $this->statusLabel((string) $log->status),
                    'status_class' => $this->statusClass((string) $log->status),
                    'contact' => $this->extractContact($log),
                    'created_at' => $this->humanDate($log->created_at),
                ];
            });

        return view('livewire.webhook-dashboard', [
            'totalToday' => $totalToday,
            'successToday' => $successToday,
            'failedToday' => $failedToday,
            'pendingQueue' => $pendingQueue,
            'latestWebhooks' => $latestWebhooks,
        ])->layout('layouts.app', [
            'title' => 'Dashboard de Webhooks',
        ]);
    }

    private function extractContact(WebhookLog $log): string
    {
        $payload = is_array($log->payload_in) ? $log->payload_in : [];
        $name = trim((string) (($payload['firstName'] ?? '').' '.($payload['lastName'] ?? '')));
        $phone = (string) ($payload['whatsappNumber'] ?? $payload['contact']['phone'] ?? $payload['phone'] ?? '');
        $leadId = (string) ($payload['data']['FIELDS']['ID'] ?? $log->external_id ?? '');

        if ($name !== '') {
            return $name;
        }
        if ($phone !== '') {
            return $phone;
        }

        return $leadId !== '' ? "#{$leadId}" : '-';
    }

    private function directionLabel(string $direction): string
    {
        return match ($direction) {
            WebhookDirection::BotmakerToBitrix->value => 'WhatsApp -> CRM',
            WebhookDirection::BitrixToBotmaker->value => 'CRM -> WhatsApp',
            default => $direction,
        };
    }

    private function directionIcon(string $direction): string
    {
        return $direction === WebhookDirection::BotmakerToBitrix->value ? '->' : '<-';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            WebhookStatus::Sent->value => 'Enviado',
            WebhookStatus::Failed->value => 'Fallido',
            WebhookStatus::Processing->value => 'Procesando',
            default => 'Recibido',
        };
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            WebhookStatus::Sent->value => 'status-resolved',
            WebhookStatus::Failed->value => 'status-exhausted',
            WebhookStatus::Processing->value => 'status-retrying',
            default => 'status-pending',
        };
    }

    private function humanDate(?Carbon $date): string
    {
        if (! $date) {
            return '-';
        }

        if ($date->isToday()) {
            return 'hoy '.$date->format('H:i');
        }
        if ($date->isYesterday()) {
            return 'ayer '.$date->format('H:i');
        }
        if ($date->greaterThan(now()->subHours(6))) {
            return $date->diffForHumans();
        }

        return $date->format('d/m/Y H:i');
    }
}
