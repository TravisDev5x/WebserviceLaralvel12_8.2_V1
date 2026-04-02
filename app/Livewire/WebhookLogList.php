<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\WebhookDirection;
use App\Enums\WebhookStatus;
use App\Models\WebhookLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookLogList extends Component
{
    use WithPagination;

    public string $directionFilter = 'all';

    public string $statusFilter = 'all';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function updatingDirectionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $webhooks = $this->baseQuery()->latest()->paginate(15);

        return view('livewire.webhook-log-list', [
            'webhooks' => $webhooks,
            'directions' => [
                'all' => 'Todas',
                WebhookDirection::BotmakerToBitrix->value => WebhookDirection::BotmakerToBitrix->label(),
                WebhookDirection::BitrixToBotmaker->value => WebhookDirection::BitrixToBotmaker->label(),
            ],
            'statuses' => [
                'all' => 'Todos',
                WebhookStatus::Received->value => WebhookStatus::Received->label(),
                WebhookStatus::Processing->value => WebhookStatus::Processing->label(),
                WebhookStatus::Sent->value => WebhookStatus::Sent->label(),
                WebhookStatus::Failed->value => WebhookStatus::Failed->label(),
            ],
        ])->layout('layouts.app', [
            'title' => 'Listado de Registros de Webhooks',
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        return $this->exportWithDelimiter('webhooks-export.csv', ',');
    }

    public function exportExcel(): StreamedResponse
    {
        return $this->exportWithDelimiter('webhooks-export.xls', "\t");
    }

    private function exportWithDelimiter(string $filename, string $delimiter): StreamedResponse
    {
        $count = (clone $this->baseQuery())->count();
        if ($count > 5000) {
            abort(422, 'La exportación excede 5000 registros. Ajusta los filtros.');
        }

        $rows = $this->baseQuery()->latest()->get();

        return response()->streamDownload(function () use ($rows, $delimiter): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Dirección', 'Evento', 'Estado', 'Código HTTP', 'Error', 'Fecha'], $delimiter);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->id,
                    $row->direction,
                    $row->source_event,
                    $row->status,
                    $row->http_status,
                    $row->error_message,
                    optional($row->created_at)?->format('Y-m-d H:i:s'),
                ], $delimiter);
            }
            fclose($out);
        }, $filename);
    }

    private function baseQuery(): Builder
    {
        return WebhookLog::query()
            ->when($this->directionFilter !== 'all', function (Builder $query): void {
                $query->direction($this->directionFilter);
            })
            ->when($this->statusFilter !== 'all', function (Builder $query): void {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->dateFrom !== '', function (Builder $query): void {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo !== '', function (Builder $query): void {
                $query->whereDate('created_at', '<=', $this->dateTo);
            });
    }
}
