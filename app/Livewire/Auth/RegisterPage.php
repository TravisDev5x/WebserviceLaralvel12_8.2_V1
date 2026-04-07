<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use App\Support\UserRegistrationEmail;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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
            'email' => ['nullable', 'string', 'max:255', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string', 'min:8'],
        ], [], [
            'name' => 'nombre',
            'employeeNumber' => 'número de empleado',
            'email' => 'correo',
            'password' => 'contraseña',
        ]);

        $employeeNumber = trim((string) $validated['employeeNumber']);
        $email = UserRegistrationEmail::resolve($validated['email'] ?? null, $employeeNumber);

        // TODO: Quitar auto-verificación cuando el mailer esté listo y se reactive MustVerifyEmail + middleware verified.
        $user = User::query()->create([
            'name' => trim((string) $validated['name']),
            'employee_number' => $employeeNumber,
            'role' => 'viewer',
            'is_active' => true,
            'email' => $email,
            'password' => (string) $validated['password'],
            'email_verified_at' => now(),
        ]);

        Auth::login($user);
        request()->session()->regenerate();

        return redirect('/monitor');
    }

    public function render(): View
    {
        return view('livewire.auth.register-page')->layout('layouts.auth', ['title' => 'Registro']);
    }
}
