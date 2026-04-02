<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\WebhookDirection;
use App\Enums\WebhookStatus;
use App\Models\FailedWebhook;
use App\Models\WebhookLog;
use Illuminate\Contracts\View\View;
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
                    'source_event' => (string) $log->source_event,
                    'status' => (string) $log->status,
                    'lead_id' => $this->extractLeadId($log),
                    'created_at' => optional($log->created_at)?->format('Y-m-d H:i:s'),
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

    private function extractLeadId(WebhookLog $log): string
    {
        $payload = is_array($log->payload_in) ? $log->payload_in : [];
        $leadId = $payload['data']['FIELDS']['ID'] ?? $log->external_id;

        return (string) ($leadId ?? '-');
    }

    private function directionLabel(string $direction): string
    {
        return match ($direction) {
            WebhookDirection::BotmakerToBitrix->value => WebhookDirection::BotmakerToBitrix->label(),
            WebhookDirection::BitrixToBotmaker->value => WebhookDirection::BitrixToBotmaker->label(),
            default => $direction,
        };
    }
}
