<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProfilePage extends Component
{
    public string $name = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        $this->name = (string) $user->name;
    }

    public function updateProfile(): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [], [
            'name' => 'nombre',
        ]);

        $user->name = trim((string) $data['name']);
        $user->save();

        session()->flash('profile_ok', 'Nombre actualizado correctamente.');
    }

    public function updatePassword(): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'newPassword' => ['required', 'string', 'min:8', 'same:newPasswordConfirmation'],
            'newPasswordConfirmation' => ['required', 'string', 'min:8'],
        ], [], [
            'currentPassword' => 'contraseña actual',
            'newPassword' => 'nueva contraseña',
            'newPasswordConfirmation' => 'confirmación',
        ]);

        $user->password = (string) $this->newPassword;
        $user->save();

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);

        session()->flash('password_ok', 'Contraseña actualizada correctamente.');
    }

    public function render(): View
    {
        $user = Auth::user();
        $roleDisplay = '';
        $email = '';
        $employeeNumber = '';

        if ($user instanceof User) {
            $email = (string) $user->email;
            $employeeNumber = (string) ($user->employee_number ?? '');
            $roleDisplay = (string) (Role::query()->where('slug', $user->role)->value('name') ?? $user->role);
        }

        return view('livewire.profile-page', [
            'email' => $email,
            'employeeNumber' => $employeeNumber,
            'roleDisplay' => $roleDisplay,
        ])->layout('layouts.app', ['title' => 'Mi perfil']);
    }
}
