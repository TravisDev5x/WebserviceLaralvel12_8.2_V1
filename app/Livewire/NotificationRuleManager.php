<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\MessageTemplate;
use App\Models\NotificationRule;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationRuleManager extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public ?int $deleteId = null;
    public string $search = '';
    public string $eventFilter = 'all';
    public string $statusFilter = 'all';
    public string $name = '';
    public string $event_type = '';
    public string $condition_field = '';
    public string $condition_operator = 'equals';
    public string $condition_value = '';
    public ?int $message_template_id = null;
    public string $message_template = '';
    public bool $is_active = true;

    public function edit(int $id): void
    {
        $row = NotificationRule::query()->findOrFail($id);
        $this->editingId = $row->id;
        $this->name = (string) $row->name;
        $this->event_type = (string) $row->event_type;
        $this->condition_field = (string) ($row->condition_field ?? '');
        $this->condition_operator = (string) $row->condition_operator;
        $this->condition_value = (string) ($row->condition_value ?? '');
        $this->message_template_id = $row->message_template_id;
        $this->message_template = (string) ($row->message_template ?? '');
        $this->is_active = (bool) $row->is_active;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'event_type' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_*]+$/'],
            'condition_field' => ['nullable', 'string', 'max:100'],
            'condition_operator' => ['required', 'in:equals,not_equals,contains,changed_to,is_empty,is_not_empty'],
            'condition_value' => ['nullable', 'string', 'max:255'],
            'message_template_id' => ['nullable', 'integer', 'exists:message_templates,id'],
            'message_template' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        if (! in_array($data['condition_operator'], ['is_empty', 'is_not_empty'], true) && trim((string) $data['condition_value']) === '') {
            $this->addError('condition_value', 'Este operador requiere valor de condición.');
            return;
        }
        if (($data['message_template_id'] ?? null) === null && trim((string) $data['message_template']) === '') {
            $this->addError('message_template', 'Debes seleccionar plantilla o escribir plantilla inline.');
            return;
        }

        NotificationRule::query()->updateOrCreate(['id' => $this->editingId], $data);
        $this->reset(['editingId', 'name', 'event_type', 'condition_field', 'condition_value', 'message_template', 'message_template_id']);
        $this->condition_operator = 'equals';
        $this->is_active = true;
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId !== null) {
            NotificationRule::query()->whereKey($this->deleteId)->delete();
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

    public function updatingEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $rows = NotificationRule::query()
            ->when($this->eventFilter !== 'all', fn (Builder $q) => $q->where('event_type', $this->eventFilter))
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('event_type', 'like', $term)
                        ->orWhere('condition_field', 'like', $term);
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.notification-rule-manager', [
            'rows' => $rows,
            'templates' => MessageTemplate::query()->active()->orderBy('name')->get(),
            'events' => NotificationRule::query()->select('event_type')->distinct()->orderBy('event_type')->pluck('event_type'),
        ])->layout('layouts.app', ['title' => 'Reglas de Notificación']);
    }
}
