<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AuthorizedTokenManager extends Component
{
    public string $platformTab = 'bitrix24';

    public string $bitrixSub = 'incoming';

    public string $botmakerSub = 'api';

    public ?int $editingId = null;

    public ?int $deleteId = null;

    public string $label = '';

    public string $token = '';

    public string $webhook_url = '';

    public string $notes = '';

    public bool $is_active = true;

    /** @var list<int> */
    public array $revealedTokenIds = [];

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public ?string $testRowMessage = null;

    public function mount(): void
    {
        $this->resetFormFields();
    }

    public function updatedPlatformTab(): void
    {
        $this->resetFormFields();
        $this->revealedTokenIds = [];
        $this->reset('successMessage', 'errorMessage', 'deleteId', 'testRowMessage');
    }

    public function updatedBitrixSub(): void
    {
        $this->resetFormFields();
        $this->revealedTokenIds = [];
        $this->reset('successMessage', 'errorMessage', 'deleteId', 'testRowMessage');
    }

    public function updatedBotmakerSub(): void
    {
        $this->resetFormFields();
        $this->revealedTokenIds = [];
        $this->reset('successMessage', 'errorMessage', 'deleteId', 'testRowMessage');
    }

    public function startCreate(): void
    {
        $this->resetFormFields();
        $this->editingId = null;
        $this->reset('successMessage', 'errorMessage', 'testRowMessage');
    }

    public function edit(int $id): void
    {
        $row = AuthorizedToken::query()->findOrFail($id);
        $this->assertRowContext($row);
        $this->editingId = $row->id;
        $this->label = (string) $row->label;
        $this->token = '';
        $this->webhook_url = (string) ($row->webhook_url ?? '');
        $this->notes = (string) ($row->notes ?? '');
        $this->is_active = (bool) $row->is_active;
        $this->reset('successMessage', 'errorMessage', 'deleteId', 'testRowMessage');
    }

    public function cancelEdit(): void
    {
        $this->resetFormFields();
    }

    public function save(): void
    {
        $this->reset('successMessage', 'errorMessage', 'testRowMessage');

        $dir = $this->currentDirection();
        $platform = $this->platformTab;

        $rules = [
            'label' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ];

        if ($platform === 'bitrix24' && $dir === AuthorizedToken::DIRECTION_INCOMING) {
            $rules['webhook_url'] = ['required', 'url', 'max:500'];
            $rules['token'] = ['nullable', 'string', 'max:10000'];
        } elseif ($platform === 'bitrix24' && $dir === AuthorizedToken::DIRECTION_OUTGOING) {
            $rules['webhook_url'] = ['nullable', 'string', 'max:500'];
            if ($this->editingId === null) {
                $rules['token'] = ['required', 'string', 'max:10000'];
            } else {
                $rules['token'] = ['nullable', 'string', 'max:10000'];
            }
        } else {
            $rules['webhook_url'] = ['nullable', 'string', 'max:500'];
            if ($this->editingId === null) {
                $rules['token'] = ['required', 'string', 'max:10000'];
            } else {
                $rules['token'] = ['nullable', 'string', 'max:10000'];
            }
        }

        $this->validate($rules, [], [
            'label' => 'Etiqueta',
            'token' => 'Token',
            'webhook_url' => 'URL',
            'notes' => 'Notas',
        ]);

        if (! in_array($platform, ['bitrix24', 'botmaker'], true)) {
            $this->platformTab = 'bitrix24';
        }

        try {
            $data = [
                'platform' => $platform,
                'label' => $this->label,
                'webhook_url' => trim($this->webhook_url) !== '' ? trim($this->webhook_url) : null,
                'direction' => $dir,
                'is_active' => $this->is_active,
                'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
            ];

            if ($platform === 'bitrix24' && $dir === AuthorizedToken::DIRECTION_INCOMING) {
                $data['token'] = '';
            } elseif ($this->editingId === null) {
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

    public function testIncomingBitrix(int $id): void
    {
        $this->testRowMessage = null;
        $row = AuthorizedToken::query()->findOrFail($id);
        if ($row->platform !== 'bitrix24' || $row->direction !== AuthorizedToken::DIRECTION_INCOMING) {
            abort(403);
        }
        $base = rtrim(trim((string) ($row->webhook_url ?? '')), '/');
        if ($base === '') {
            $this->testRowMessage = 'Sin URL configurada.';

            return;
        }

        $client = new Client(['timeout' => 10]);
        try {
            $response = $client->request('GET', "{$base}/crm.lead.list?start=0&limit=1", [
                'headers' => ['Accept' => 'application/json'],
            ]);
            $status = $response->getStatusCode();
            $this->testRowMessage = $status >= 200 && $status < 300
                ? "OK (HTTP {$status})"
                : "HTTP {$status}";
        } catch (RequestException $e) {
            $st = $e->getResponse()?->getStatusCode();
            $this->testRowMessage = $st !== null ? "Error HTTP {$st}" : 'Error de red';
        } catch (\Throwable $e) {
            $this->testRowMessage = $e->getMessage();
        }
    }

    public function toggleActive(int $id): void
    {
        $row = AuthorizedToken::query()->findOrFail($id);
        $this->assertRowContext($row);
        $row->update(['is_active' => ! $row->is_active]);
        $this->reset('successMessage', 'errorMessage', 'testRowMessage');
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
            ->platform($this->platformTab)
            ->where('direction', $this->currentDirection())
            ->orderByDesc('id')
            ->get();

        return view('livewire.authorized-token-manager', [
            'rows' => $rows,
        ])->layout('layouts.app', [
            'title' => 'Webhooks autorizados',
        ]);
    }

    private function currentDirection(): string
    {
        if ($this->platformTab === 'bitrix24') {
            return $this->bitrixSub === 'incoming'
                ? AuthorizedToken::DIRECTION_INCOMING
                : AuthorizedToken::DIRECTION_OUTGOING;
        }

        return $this->botmakerSub === 'api'
            ? AuthorizedToken::DIRECTION_INCOMING
            : AuthorizedToken::DIRECTION_OUTGOING;
    }

    private function resetFormFields(): void
    {
        $this->editingId = null;
        $this->label = '';
        $this->token = '';
        $this->webhook_url = '';
        $this->notes = '';
        $this->is_active = true;
    }

    private function assertRowContext(AuthorizedToken $row): void
    {
        if ($row->platform !== $this->platformTab || (string) $row->direction !== $this->currentDirection()) {
            abort(403);
        }
    }
}
