<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\FieldMapping;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class FieldMappingManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public ?int $deleteId = null;
    public string $search = '';
    public string $platformFilter = 'all';
    public string $statusFilter = 'all';
    public string $source_platform = 'botmaker';
    public string $source_field = '';
    public string $source_path = '';
    public string $target_platform = 'bitrix24';
    public string $target_field = '';
    public string $target_path = '';
    public string $transform_type = 'none';
    public string $transform_config = '{}';
    public bool $is_active = true;

    public function edit(int $id): void
    {
        $row = FieldMapping::query()->findOrFail($id);
        $this->editingId = $row->id;
        $this->source_platform = (string) $row->source_platform;
        $this->source_field = (string) $row->source_field;
        $this->source_path = (string) $row->source_path;
        $this->target_platform = (string) $row->target_platform;
        $this->target_field = (string) $row->target_field;
        $this->target_path = (string) $row->target_path;
        $this->transform_type = (string) ($row->transform_type ?? 'none');
        $this->transform_config = json_encode($row->transform_config ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
        $this->is_active = (bool) $row->is_active;
    }

    public function save(): void
    {
        $data = $this->validate([
            'source_platform' => ['required', 'in:botmaker,bitrix24'],
            'source_field' => ['required', 'string', 'max:100'],
            'source_path' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._\[\]-]+$/'],
            'target_platform' => ['required', 'in:botmaker,bitrix24'],
            'target_field' => ['required', 'string', 'max:100'],
            'target_path' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._\[\]-]+$/'],
            'transform_type' => ['required', 'in:none,uppercase,lowercase,trim,date_format,currency,catalog'],
            'transform_config' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $config = json_decode((string) $data['transform_config'], true);
        if ((string) $data['transform_config'] !== '' && ! is_array($config)) {
            $this->addError('transform_config', 'JSON inválido.');
            return;
        }
        if ($data['transform_type'] === 'catalog' && ! isset($config['map']) && ! is_array($config)) {
            $this->addError('transform_config', 'Para catalog usa {"map":{"etiqueta":"valor"}}.');
            return;
        }
        if ($data['transform_type'] === 'date_format' && isset($config['format']) && ! is_string($config['format'])) {
            $this->addError('transform_config', 'Para date_format usa {"format":"Y-m-d"}');
            return;
        }

        FieldMapping::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                ...$data,
                'transform_config' => $config,
            ],
        );

        $this->reset(['editingId', 'source_field', 'source_path', 'target_field', 'target_path', 'transform_config']);
        $this->transform_type = 'none';
        $this->dispatch('notify', 'Mapeo guardado.');
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId !== null) {
            FieldMapping::query()->whereKey($this->deleteId)->delete();
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
        $rows = FieldMapping::query()
            ->when($this->platformFilter !== 'all', fn (Builder $q) => $q->where('source_platform', $this->platformFilter))
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('source_field', 'like', $term)
                        ->orWhere('target_field', 'like', $term)
                        ->orWhere('source_path', 'like', $term)
                        ->orWhere('target_path', 'like', $term);
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.field-mapping-manager', [
            'rows' => $rows,
        ])->layout('layouts.app', ['title' => 'Mapeo de Campos']);
    }
}
