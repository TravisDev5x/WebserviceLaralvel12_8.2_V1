<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\WhatsappNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class WhatsappNumberManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public ?int $deleteId = null;
    public string $search = '';
    public string $statusFilter = 'all';
    public string $phone_number = '';
    public string $label = '';
    public string $platform_id = '';
    public bool $is_active = true;
    public bool $is_default = false;

    public function edit(int $id): void
    {
        $row = WhatsappNumber::query()->findOrFail($id);
        $this->editingId = $row->id;
        $this->phone_number = (string) $row->phone_number;
        $this->label = (string) $row->label;
        $this->platform_id = (string) ($row->platform_id ?? '');
        $this->is_active = (bool) $row->is_active;
        $this->is_default = (bool) $row->is_default;
    }

    public function save(): void
    {
        $data = $this->validate([
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{10,15}$/'],
            'label' => ['required', 'string', 'max:100'],
            'platform_id' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ]);

        if ($data['is_active'] && trim((string) $data['platform_id']) === '') {
            $this->addError('platform_id', 'Platform ID es requerido para números activos.');
            return;
        }

        if ((bool) $data['is_default']) {
            WhatsappNumber::query()->update(['is_default' => false]);
        }

        WhatsappNumber::query()->updateOrCreate(['id' => $this->editingId], $data);
        $this->reset(['editingId', 'phone_number', 'label', 'platform_id']);
        $this->is_active = true;
        $this->is_default = false;
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId !== null) {
            WhatsappNumber::query()->whereKey($this->deleteId)->delete();
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

    public function render(): View
    {
        $rows = WhatsappNumber::query()
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('phone_number', 'like', $term)
                        ->orWhere('label', 'like', $term)
                        ->orWhere('platform_id', 'like', $term);
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.whatsapp-number-manager', [
            'rows' => $rows,
        ])->layout('layouts.app', ['title' => 'Números WhatsApp']);
    }
}
