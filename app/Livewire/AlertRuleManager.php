<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AlertRule;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class AlertRuleManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public ?int $deleteId = null;
    public string $search = '';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all';
    public string $name = '';
    public string $condition_type = 'failed_webhooks';
    public int $threshold = 10;
    public int $time_window_minutes = 60;
    public string $notify_email = '';
    public bool $is_active = true;
    public int $cooldown_minutes = 60;

    public function edit(int $id): void
    {
        $row = AlertRule::query()->findOrFail($id);
        $this->editingId = $row->id;
        $this->name = (string) $row->name;
        $this->condition_type = (string) $row->condition_type;
        $this->threshold = (int) $row->threshold;
        $this->time_window_minutes = (int) $row->time_window_minutes;
        $this->notify_email = (string) $row->notify_email;
        $this->is_active = (bool) $row->is_active;
        $this->cooldown_minutes = (int) $row->cooldown_minutes;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'condition_type' => ['required', 'in:failed_webhooks,webhook_errors,queue_stuck'],
            'threshold' => ['required', 'integer', 'min:1', 'max:5000'],
            'time_window_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'notify_email' => ['required', 'email', 'max:255'],
            'is_active' => ['boolean'],
            'cooldown_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        AlertRule::query()->updateOrCreate(['id' => $this->editingId], $data);
        $this->reset(['editingId', 'name', 'notify_email']);
        $this->condition_type = 'failed_webhooks';
        $this->threshold = 10;
        $this->time_window_minutes = 60;
        $this->cooldown_minutes = 60;
        $this->is_active = true;
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId !== null) {
            AlertRule::query()->whereKey($this->deleteId)->delete();
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

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $rows = AlertRule::query()
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->when($this->typeFilter !== 'all', fn (Builder $q) => $q->where('condition_type', $this->typeFilter))
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)->orWhere('notify_email', 'like', $term);
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.alert-rule-manager', [
            'rows' => $rows,
        ])->layout('layouts.app', ['title' => 'Alertas por Correo']);
    }
}
