<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = 'all';

    public string $statusFilter = 'all';

    public bool $showCreateUserModal = false;

    public string $newUserName = '';

    public string $newUserEmail = '';

    public string $newUserEmployeeNumber = '';

    public string $newUserPassword = '';

    public string $newUserPasswordConfirmation = '';

    public string $newUserRole = 'viewer';

    public function openCreateUser(): void
    {
        $this->reset([
            'newUserName',
            'newUserEmail',
            'newUserEmployeeNumber',
            'newUserPassword',
            'newUserPasswordConfirmation',
        ]);
        $this->newUserRole = 'viewer';
        $this->showCreateUserModal = true;
    }

    public function closeCreateUser(): void
    {
        $this->reset([
            'newUserName',
            'newUserEmail',
            'newUserEmployeeNumber',
            'newUserPassword',
            'newUserPasswordConfirmation',
        ]);
        $this->newUserRole = 'viewer';
        $this->showCreateUserModal = false;
    }

    public function createUser(): void
    {
        $data = $this->validate([
            'newUserName' => ['required', 'string', 'max:255'],
            'newUserEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'newUserEmployeeNumber' => ['required', 'string', 'max:100', 'unique:users,employee_number'],
            'newUserPassword' => ['required', 'string', 'min:8', 'same:newUserPasswordConfirmation'],
            'newUserPasswordConfirmation' => ['required', 'string', 'min:8'],
            'newUserRole' => ['required', 'string', 'in:admin,operator,viewer'],
        ], [], [
            'newUserName' => 'nombre',
            'newUserEmail' => 'correo',
            'newUserEmployeeNumber' => 'número de empleado',
            'newUserPassword' => 'contraseña',
            'newUserPasswordConfirmation' => 'confirmación',
        ]);

        $user = User::query()->create([
            'name' => trim((string) $data['newUserName']),
            'email' => strtolower(trim((string) $data['newUserEmail'])),
            'employee_number' => trim((string) $data['newUserEmployeeNumber']),
            'password' => (string) $data['newUserPassword'],
            'role' => (string) $data['newUserRole'],
            'is_active' => true,
        ]);

        $user->sendEmailVerificationNotification();

        $this->closeCreateUser();
        $this->resetPage();
        session()->flash('user_created', 'Usuario creado. Se envió correo de verificación si está configurado.');
    }

    public function toggleActive(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $user->is_active = ! $user->is_active;
        $user->save();
    }

    public function setRole(int $id, string $role): void
    {
        if (! in_array($role, ['admin', 'operator', 'viewer'], true)) {
            return;
        }
        User::query()->whereKey($id)->update(['role' => $role]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $users = User::query()
            ->when($this->roleFilter !== 'all', fn (Builder $q) => $q->where('role', $this->roleFilter))
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('employee_number', 'like', $term);
                });
            })
            ->latest()
            ->paginate(20);

        return view('livewire.user-manager', [
            'users' => $users,
        ])->layout('layouts.app', ['title' => 'Usuarios y Roles']);
    }
}
