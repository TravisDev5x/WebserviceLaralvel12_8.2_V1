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
