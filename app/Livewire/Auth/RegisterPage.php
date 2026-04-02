<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RegisterPage extends Component
{
    public string $name = '';

    public string $employeeNumber = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function register(): mixed
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'employeeNumber' => ['required', 'string', 'max:100', 'unique:users,employee_number'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string', 'min:8'],
        ], [], [
            'name' => 'nombre',
            'employeeNumber' => 'número de empleado',
            'email' => 'correo',
            'password' => 'contraseña',
        ]);

        $user = User::query()->create([
            'name' => trim((string) $validated['name']),
            'employee_number' => trim((string) $validated['employeeNumber']),
            'role' => 'operator',
            'is_active' => true,
            'email' => strtolower(trim((string) $validated['email'])),
            'password' => (string) $validated['password'],
        ]);

        Auth::login($user);
        request()->session()->regenerate();

        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }

    public function render(): View
    {
        return view('livewire.auth.register-page')->layout('layouts.auth', ['title' => 'Registro']);
    }
}
