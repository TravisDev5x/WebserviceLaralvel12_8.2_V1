<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AuthorizedTokenManager extends Component
{
    public ?int $editingId = null;

    public ?int $deleteId = null;

    public string $label = '';

    public string $token = '';

    public string $notes = '';

    public bool $is_active = true;

    /** @var list<int> */
    public array $revealedTokenIds = [];

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->resetFormFields();
    }

    public function startCreate(): void
    {
        $this->resetFormFields();
        $this->editingId = null;
        $this->reset('successMessage', 'errorMessage');
    }

    public function edit(int $id): void
    {
        $row = AuthorizedToken::query()->findOrFail($id);
        $this->assertRowContext($row);
        $this->editingId = $row->id;
        $this->label = (string) $row->label;
        $this->token = '';
        $this->notes = (string) ($row->notes ?? '');
        $this->is_active = (bool) $row->is_active;
        $this->reset('successMessage', 'errorMessage', 'deleteId');
    }

    public function cancelEdit(): void
    {
        $this->resetFormFields();
    }

    public function save(): void
    {
        $this->reset('successMessage', 'errorMessage');

        $rules = [
            'label' => ['required', 'string', 'max:100'],
            'token' => $this->editingId === null ? ['required', 'string', 'max:10000'] : ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ];

        $this->validate($rules, [], [
            'label' => 'Etiqueta',
            'token' => 'Token',
            'notes' => 'Notas',
        ]);

        try {
            $data = [
                'platform' => 'botmaker',
                'label' => $this->label,
                'webhook_url' => null,
                'direction' => AuthorizedToken::DIRECTION_OUTGOING,
                'is_active' => $this->is_active,
                'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
            ];

            if ($this->editingId === null) {
                $data['token'] = $this->token;
            } else {
                $row = AuthorizedToken::query()->findOrFail($this->editingId);
                $this->assertRowContext($row);
                if ($this->token !== '') {
                    $data['token'] = $this->token;
                }
                $row->fill($data);
                $row->save();
                $this->successMessage = 'Registro actualizado.';
                $this->resetFormFields();

                return;
            }

            if ($this->editingId === null) {
                AuthorizedToken::query()->create($data);
                $this->successMessage = 'Registro creado.';
            }

            $this->resetFormFields();
        } catch (\Throwable $e) {
            $this->errorMessage = 'No se pudo guardar: '.$e->getMessage();
        }
    }

    public function toggleActive(int $id): void
    {
        $row = AuthorizedToken::query()->findOrFail($id);
        $this->assertRowContext($row);
        $row->update(['is_active' => ! $row->is_active]);
        $this->reset('successMessage', 'errorMessage');
    }

    public function confirmDelete(int $id): void
    {
        $row = AuthorizedToken::query()->findOrFail($id);
        $this->assertRowContext($row);
        $this->deleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deleteId = null;
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $row = AuthorizedToken::query()->findOrFail($this->deleteId);
        $this->assertRowContext($row);
        $removedId = $row->id;
        $row->delete();
        $this->deleteId = null;
        $this->revealedTokenIds = array_values(array_diff($this->revealedTokenIds, [$removedId]));
        $this->successMessage = 'Registro eliminado.';
    }

    public function toggleReveal(int $id): void
    {
        if (in_array($id, $this->revealedTokenIds, true)) {
            $this->revealedTokenIds = array_values(array_diff($this->revealedTokenIds, [$id]));
        } else {
            $this->revealedTokenIds[] = $id;
        }
    }

    public function render(): View
    {
        $rows = AuthorizedToken::query()
            ->platform('botmaker')
            ->where('direction', AuthorizedToken::DIRECTION_OUTGOING)
            ->orderByDesc('id')
            ->get();

        return view('livewire.authorized-token-manager', [
            'rows' => $rows,
        ])->layout('layouts.app', [
            'title' => 'Webhooks autorizados',
        ]);
    }

    private function resetFormFields(): void
    {
        $this->editingId = null;
        $this->label = '';
        $this->token = '';
        $this->notes = '';
        $this->is_active = true;
    }

    private function assertRowContext(AuthorizedToken $row): void
    {
        if ($row->platform !== 'botmaker' || (string) $row->direction !== AuthorizedToken::DIRECTION_OUTGOING) {
            abort(403);
        }
    }
}
