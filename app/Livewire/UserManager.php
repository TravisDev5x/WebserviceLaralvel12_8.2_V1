<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use App\Support\UserRegistrationEmail;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
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

    public bool $showEditUserModal = false;

    public ?int $editUserId = null;

    public string $editUserName = '';

    public string $editUserEmail = '';

    public string $editUserEmployeeNumber = '';

    public string $editUserRole = 'viewer';

    public bool $showRoleConfirmModal = false;

    public ?int $roleModalUserId = null;

    public string $roleModalName = '';

    public string $roleModalOld = '';

    public string $roleModalNew = '';

    public int $tableVersion = 0;

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
            'newUserEmail' => ['nullable', 'string', 'max:255', 'email', Rule::unique('users', 'email')],
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

        $employeeNumber = trim((string) $data['newUserEmployeeNumber']);
        $email = UserRegistrationEmail::resolve($data['newUserEmail'] ?? null, $employeeNumber);

        // TODO: Quitar auto-verificación cuando el mailer esté listo y se reactive MustVerifyEmail + middleware verified.
        User::query()->create([
            'name' => trim((string) $data['newUserName']),
            'email' => $email,
            'employee_number' => $employeeNumber,
            'password' => (string) $data['newUserPassword'],
            'role' => (string) $data['newUserRole'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->closeCreateUser();
        $this->resetPage();
        session()->flash('user_success', 'Usuario creado correctamente.');
    }

    public function openEditUser(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->editUserId = $user->id;
        $this->editUserName = (string) $user->name;
        $this->editUserEmail = $user->usesSyntheticEmail() ? '' : (string) $user->email;
        $this->editUserEmployeeNumber = (string) ($user->employee_number ?? '');
        $this->editUserRole = (string) ($user->role ?: 'viewer');
        $this->showEditUserModal = true;
    }

    public function closeEditUser(): void
    {
        $this->reset(['editUserId', 'editUserName', 'editUserEmail', 'editUserEmployeeNumber', 'editUserRole']);
        $this->showEditUserModal = false;
    }

    public function saveEditUser(): void
    {
        if ($this->editUserId === null) {
            return;
        }

        $data = $this->validate([
            'editUserName' => ['required', 'string', 'max:255'],
            'editUserEmail' => ['nullable', 'string', 'max:255', 'email', Rule::unique('users', 'email')->ignore($this->editUserId)],
            'editUserEmployeeNumber' => ['required', 'string', 'max:100', Rule::unique('users', 'employee_number')->ignore($this->editUserId)],
            'editUserRole' => ['required', 'string', 'in:admin,operator,viewer'],
        ], [], [
            'editUserName' => 'nombre',
            'editUserEmail' => 'correo',
            'editUserEmployeeNumber' => 'número de empleado',
        ]);

        $target = User::query()->findOrFail($this->editUserId);
        if ($target->id === auth()->id() && $data['editUserRole'] !== $target->role) {
            session()->flash('user_error', 'No puede cambiar su propio rol desde aquí.');

            return;
        }

        if ($target->role === 'admin' && $data['editUserRole'] !== 'admin' && ! $this->otherActiveAdminExists($target->id)) {
            session()->flash('user_error', 'Debe existir al menos otro administrador activo antes de cambiar este rol.');

            return;
        }

        $employeeNumber = trim((string) $data['editUserEmployeeNumber']);
        $email = UserRegistrationEmail::resolve($data['editUserEmail'] ?? null, $employeeNumber);

        $target->update([
            'name' => trim((string) $data['editUserName']),
            'email' => $email,
            'employee_number' => $employeeNumber,
            'role' => (string) $data['editUserRole'],
        ]);

        $this->closeEditUser();
        session()->flash('user_success', 'Usuario actualizado correctamente.');
    }

    public function promptRoleChange(int $userId, string $newRole, string $oldRole, string $displayName): void
    {
        if ($newRole === $oldRole || ! in_array($newRole, ['admin', 'operator', 'viewer'], true)) {
            $this->tableVersion++;

            return;
        }

        $this->roleModalUserId = $userId;
        $this->roleModalNew = $newRole;
        $this->roleModalOld = $oldRole;
        $this->roleModalName = $displayName;
        $this->showRoleConfirmModal = true;
    }

    public function cancelRoleModal(): void
    {
        $this->showRoleConfirmModal = false;
        $this->roleModalUserId = null;
        $this->tableVersion++;
    }

    public function confirmRoleModal(): void
    {
        if ($this->roleModalUserId === null) {
            $this->cancelRoleModal();

            return;
        }

        $id = $this->roleModalUserId;
        $role = $this->roleModalNew;
        $this->showRoleConfirmModal = false;
        $this->roleModalUserId = null;

        $this->applyRoleUpdate($id, $role);
    }

    public function applyRoleUpdate(int $id, string $role): void
    {
        if (! in_array($role, ['admin', 'operator', 'viewer'], true)) {
            return;
        }

        if ($id === auth()->id()) {
            session()->flash('user_error', 'No puede cambiar su propio rol.');
            $this->tableVersion++;

            return;
        }

        $user = User::query()->findOrFail($id);

        if ($user->role === 'admin' && $role !== 'admin' && ! $this->otherActiveAdminExists($user->id)) {
            session()->flash('user_error', 'No puede quitar el rol de administrador al único administrador activo.');
            $this->tableVersion++;

            return;
        }

        $user->update(['role' => $role]);
        session()->flash('user_success', 'Rol actualizado correctamente.');
    }

    public function toggleActive(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('user_error', 'No puede desactivar su propio usuario.');

            return;
        }

        $user = User::query()->findOrFail($id);

        if ($user->is_active && $user->role === 'admin' && ! $this->otherActiveAdminExists($user->id)) {
            session()->flash('user_error', 'No puede desactivar al único administrador activo.');

            return;
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        session()->flash('user_success', $user->is_active ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    private function otherActiveAdminExists(int $exceptUserId): bool
    {
        return User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->whereKeyNot($exceptUserId)
            ->exists();
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
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.user-manager', [
            'users' => $users,
        ])->layout('layouts.app', ['title' => 'Usuarios y Roles']);
    }
}
