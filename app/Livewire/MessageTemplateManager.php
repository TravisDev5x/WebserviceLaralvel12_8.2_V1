<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\MessageTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class MessageTemplateManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public ?int $deleteId = null;
    public string $search = '';
    public string $categoryFilter = 'all';
    public string $statusFilter = 'all';
    public string $name = '';
    public string $category = 'custom';
    public string $body = '';
    public string $variables_available = 'nombre,apellido,telefono,estatus,lead_id,agente,fecha';
    public bool $is_active = true;

    public function edit(int $id): void
    {
        $row = MessageTemplate::query()->findOrFail($id);
        $this->editingId = $row->id;
        $this->name = (string) $row->name;
        $this->category = (string) $row->category;
        $this->body = (string) $row->body;
        $this->variables_available = implode(',', (array) ($row->variables_available ?? []));
        $this->is_active = (bool) $row->is_active;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:notification,confirmation,follow_up,custom'],
            'body' => ['required', 'string'],
            'variables_available' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $vars = array_values(array_filter(array_map('trim', explode(',', (string) $data['variables_available']))));
        foreach ($vars as $var) {
            if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $var)) {
                $this->addError('variables_available', "Variable inválida: {$var}");
                return;
            }
        }

        MessageTemplate::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'category' => $data['category'],
                'body' => $data['body'],
                'variables_available' => $vars,
                'is_active' => $data['is_active'],
            ],
        );

        $this->reset(['editingId', 'name', 'body']);
        $this->category = 'custom';
        $this->variables_available = 'nombre,apellido,telefono,estatus,lead_id,agente,fecha';
        $this->is_active = true;
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId !== null) {
            MessageTemplate::query()->whereKey($this->deleteId)->delete();
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

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $rows = MessageTemplate::query()
            ->when($this->categoryFilter !== 'all', fn (Builder $q) => $q->where('category', $this->categoryFilter))
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)->orWhere('body', 'like', $term);
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.message-template-manager', [
            'rows' => $rows,
        ])->layout('layouts.app', ['title' => 'Plantillas de Mensajes']);
    }
}
