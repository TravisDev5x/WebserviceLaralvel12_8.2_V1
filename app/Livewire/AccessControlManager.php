<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class AccessControlManager extends Component
{
    use WithPagination;

    public string $userSearch = '';
    public string $userRoleFilter = 'all';
    public string $userStatusFilter = 'all';
    public int $usersPerPage = 12;
    public string $userSortBy = 'created_at';
    public string $userSortDir = 'desc';
    public ?int $editingUserId = null;
    public string $editUserName = '';
    public string $editUserEmail = '';
    public string $editUserEmployeeNumber = '';
    public string $editUserRole = 'viewer';
    public bool $editUserIsActive = true;
    public bool $showUserEditor = false;
    public ?int $passwordUserId = null;
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public bool $showPasswordEditor = false;
    public ?int $confirmUserId = null;
    public string $confirmAction = '';
    public bool $showConfirmActionModal = false;

    public ?int $editingRoleId = null;
    public string $roleName = '';
    public string $roleSlug = '';
    public string $roleDescription = '';
    public bool $roleIsActive = true;
    /** @var array<int, string> */
    public array $selectedPermissions = [];

    public function updatingUserSearch(): void
    {
        $this->resetPage('users_page');
    }

    public function updatingUserRoleFilter(): void
    {
        $this->resetPage('users_page');
    }

    public function updatingUserStatusFilter(): void
    {
        $this->resetPage('users_page');
    }

    public function updatingUsersPerPage(): void
    {
        $this->resetPage('users_page');
    }

    public function clearUserFilters(): void
    {
        $this->reset(['userSearch', 'userRoleFilter', 'userStatusFilter']);
        $this->userRoleFilter = 'all';
        $this->userStatusFilter = 'all';
        $this->resetPage('users_page');
    }

    public function sortUsersBy(string $field): void
    {
        $allowed = ['created_at', 'name', 'email', 'employee_number', 'role'];
        if (! in_array($field, $allowed, true)) {
            return;
        }

        if ($this->userSortBy === $field) {
            $this->userSortDir = $this->userSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->userSortBy = $field;
            $this->userSortDir = 'asc';
        }
    }

    public function assignRole(int $userId, string $roleSlug): void
    {
        if (! Role::query()->where('slug', $roleSlug)->exists()) {
            return;
        }
        User::withTrashed()->whereKey($userId)->update(['role' => $roleSlug]);
    }

    public function toggleUserStatus(int $userId): void
    {
        $user = User::withTrashed()->findOrFail($userId);
        if ($user->trashed()) {
            return;
        }
        $user->is_active = ! $user->is_active;
        $user->save();
    }

    public function editUser(int $userId): void
    {
        $user = User::withTrashed()->findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->editUserName = (string) $user->name;
        $this->editUserEmail = (string) $user->email;
        $this->editUserEmployeeNumber = (string) ($user->employee_number ?? '');
        $this->editUserRole = (string) $user->role;
        $this->editUserIsActive = (bool) $user->is_active;
        $this->showUserEditor = true;
    }

    public function saveUser(): void
    {
        if ($this->editingUserId === null) {
            return;
        }

        $data = $this->validate([
            'editUserName' => ['required', 'string', 'max:255'],
            'editUserEmail' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->editingUserId],
            'editUserEmployeeNumber' => ['required', 'string', 'max:100', 'unique:users,employee_number,'.$this->editingUserId],
            'editUserRole' => ['required', 'string', 'exists:roles,slug'],
            'editUserIsActive' => ['boolean'],
        ]);

        User::withTrashed()->whereKey($this->editingUserId)->update([
            'name' => trim((string) $data['editUserName']),
            'email' => strtolower(trim((string) $data['editUserEmail'])),
            'employee_number' => trim((string) $data['editUserEmployeeNumber']),
            'role' => (string) $data['editUserRole'],
            'is_active' => (bool) $data['editUserIsActive'],
        ]);

        $this->closeUserEditor();
    }

    public function closeUserEditor(): void
    {
        $this->reset(['editingUserId', 'editUserName', 'editUserEmail', 'editUserEmployeeNumber', 'editUserRole']);
        $this->editUserIsActive = true;
        $this->showUserEditor = false;
    }

    public function openPasswordEditor(int $userId): void
    {
        $this->passwordUserId = $userId;
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->showPasswordEditor = true;
    }

    public function saveUserPassword(): void
    {
        if ($this->passwordUserId === null) {
            return;
        }
        $data = $this->validate([
            'newPassword' => ['required', 'string', 'min:8', 'same:newPasswordConfirmation'],
            'newPasswordConfirmation' => ['required', 'string', 'min:8'],
        ], [], [
            'newPassword' => 'nueva contraseña',
            'newPasswordConfirmation' => 'confirmación',
        ]);

        User::withTrashed()->whereKey($this->passwordUserId)->update([
            'password' => Hash::make((string) $data['newPassword']),
        ]);

        $this->closePasswordEditor();
    }

    public function closePasswordEditor(): void
    {
        $this->reset(['passwordUserId', 'newPassword', 'newPasswordConfirmation']);
        $this->showPasswordEditor = false;
    }

    public function deactivateUser(int $userId): void
    {
        if (auth()->id() === $userId) {
            $this->addError('userSearch', 'No puedes darte de baja a ti mismo.');
            return;
        }
        $user = User::query()->findOrFail($userId);
        $user->is_active = false;
        $user->save();
        $user->delete();
    }

    public function askDeactivateUser(int $userId): void
    {
        if (auth()->id() === $userId) {
            $this->addError('userSearch', 'No puedes darte de baja a ti mismo.');
            return;
        }
        $this->confirmUserId = $userId;
        $this->confirmAction = 'deactivate';
        $this->showConfirmActionModal = true;
    }

    public function restoreUser(int $userId): void
    {
        $user = User::withTrashed()->findOrFail($userId);
        if (! $user->trashed()) {
            return;
        }
        $user->restore();
        $user->is_active = true;
        $user->save();
    }

    public function askRestoreUser(int $userId): void
    {
        $this->confirmUserId = $userId;
        $this->confirmAction = 'restore';
        $this->showConfirmActionModal = true;
    }

    public function confirmUserAction(): void
    {
        if ($this->confirmUserId === null) {
            return;
        }

        if ($this->confirmAction === 'deactivate') {
            $this->deactivateUser($this->confirmUserId);
        }

        if ($this->confirmAction === 'restore') {
            $this->restoreUser($this->confirmUserId);
        }

        $this->cancelUserAction();
    }

    public function cancelUserAction(): void
    {
        $this->reset(['confirmUserId', 'confirmAction']);
        $this->showConfirmActionModal = false;
    }

    public function editRole(int $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);
        $this->editingRoleId = $role->id;
        $this->roleName = (string) $role->name;
        $this->roleSlug = (string) $role->slug;
        $this->roleDescription = (string) ($role->description ?? '');
        $this->roleIsActive = (bool) $role->is_active;
        $this->selectedPermissions = is_array($role->permissions) ? $role->permissions : [];
    }

    public function newRole(): void
    {
        $this->reset(['editingRoleId', 'roleName', 'roleSlug', 'roleDescription', 'selectedPermissions']);
        $this->roleIsActive = true;
    }

    public function saveRole(): void
    {
        $data = $this->validate([
            'roleName' => ['required', 'string', 'max:100'],
            'roleSlug' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/'],
            'roleDescription' => ['nullable', 'string', 'max:255'],
            'roleIsActive' => ['boolean'],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['string'],
        ], [], [
            'roleName' => 'nombre de rol',
            'roleSlug' => 'slug de rol',
        ]);

        $catalog = permissions_catalog();
        $permissions = [];
        foreach ((array) $data['selectedPermissions'] as $permission) {
            if ($permission === '*' || in_array($permission, $catalog, true)) {
                $permissions[] = $permission;
            }
        }
        if ($permissions === []) {
            $this->addError('selectedPermissions', 'Selecciona al menos un permiso.');
            return;
        }

        Role::query()->updateOrCreate(
            ['id' => $this->editingRoleId],
            [
                'name' => $data['roleName'],
                'slug' => $data['roleSlug'],
                'description' => $data['roleDescription'],
                'is_active' => $data['roleIsActive'],
                'permissions' => array_values(array_unique($permissions)),
            ],
        );

        $this->newRole();
    }

    public function render(): View
    {
        $users = User::withTrashed()
            ->when($this->userRoleFilter !== 'all', fn (Builder $q) => $q->where('role', $this->userRoleFilter))
            ->when($this->userStatusFilter !== 'all', function (Builder $q): void {
                if ($this->userStatusFilter === 'deleted') {
                    $q->onlyTrashed();
                    return;
                }
                if ($this->userStatusFilter === 'active') {
                    $q->where('is_active', true)->whereNull('deleted_at');
                    return;
                }
                if ($this->userStatusFilter === 'inactive') {
                    $q->where('is_active', false)->whereNull('deleted_at');
                }
            })
            ->when($this->userSearch !== '', function (Builder $q): void {
                $term = '%'.$this->userSearch.'%';
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('employee_number', 'like', $term);
                });
            })
            ->orderBy($this->userSortBy, $this->userSortDir)
            ->paginate(max(5, min(50, $this->usersPerPage)), ['*'], 'users_page');

        return view('livewire.access-control-manager', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
            'permissionsCatalog' => permissions_catalog(),
            'permissionLabels' => [
                '*' => 'Acceso total',
                'monitor.view' => 'Ver tablero',
                'logs.view' => 'Ver registros',
                'failed.view' => 'Ver webhooks fallidos',
                'settings.manage' => 'Gestionar configuración',
                'mappings.manage' => 'Gestionar mapeos',
                'notifications.manage' => 'Gestionar reglas de notificación',
                'templates.manage' => 'Gestionar plantillas',
                'whatsapp.manage' => 'Gestionar números WhatsApp',
                'filters.manage' => 'Gestionar filtros de eventos',
                'alerts.manage' => 'Gestionar alertas',
                'users.manage' => 'Gestionar usuarios y roles',
            ],
        ])->layout('layouts.app', ['title' => 'Usuarios, Roles y Permisos']);
    }
}
