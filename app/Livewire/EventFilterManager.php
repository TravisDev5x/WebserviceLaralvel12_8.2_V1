<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\EventFilter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class EventFilterManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public ?int $deleteId = null;
    public string $search = '';
    public string $platformFilter = 'all';
    public string $statusFilter = 'all';
    public string $platform = 'botmaker';
    public string $event_type = '*';
    public string $filter_field = 'event';
    public string $filter_operator = 'equals';
    public string $filter_value = '';
    public string $action = 'process';
    public string $description = '';
    public bool $is_active = true;

    public function edit(int $id): void
    {
        $row = EventFilter::query()->findOrFail($id);
        $this->editingId = $row->id;
        $this->platform = (string) $row->platform;
        $this->event_type = (string) $row->event_type;
        $this->filter_field = (string) $row->filter_field;
        $this->filter_operator = (string) $row->filter_operator;
        $this->filter_value = (string) ($row->filter_value ?? '');
        $this->action = (string) $row->action;
        $this->description = (string) ($row->description ?? '');
        $this->is_active = (bool) $row->is_active;
    }

    public function save(): void
    {
        $data = $this->validate([
            'platform' => ['required', 'in:botmaker,bitrix24'],
            'event_type' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_*]+$/'],
            'filter_field' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._\[\]-]+$/'],
            'filter_operator' => ['required', 'in:equals,not_equals,contains,not_contains,is_empty,is_not_empty'],
            'filter_value' => ['nullable', 'string', 'max:255'],
            'action' => ['required', 'in:process,ignore'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
        if (! in_array($data['filter_operator'], ['is_empty', 'is_not_empty'], true) && trim((string) $data['filter_value']) === '') {
            $this->addError('filter_value', 'Este operador requiere valor.');
            return;
        }

        EventFilter::query()->updateOrCreate(['id' => $this->editingId], $data);
        $this->reset(['editingId', 'filter_value', 'description']);
        $this->platform = 'botmaker';
        $this->event_type = '*';
        $this->filter_field = 'event';
        $this->filter_operator = 'equals';
        $this->action = 'process';
        $this->is_active = true;
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId !== null) {
            EventFilter::query()->whereKey($this->deleteId)->delete();
        }
        $this->deleteId = null;
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPlatformFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $rows = EventFilter::query()
            ->when($this->platformFilter !== 'all', fn (Builder $q) => $q->where('platform', $this->platformFilter))
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('event_type', 'like', $term)
                        ->orWhere('filter_field', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.event-filter-manager', [
            'rows' => $rows,
        ])->layout('layouts.app', ['title' => 'Filtros de Eventos']);
    }
}
