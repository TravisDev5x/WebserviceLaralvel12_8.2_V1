<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class LoginPage extends Component
{
    public string $login = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): mixed
    {
        $validated = $this->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ], [], [
            'login' => 'correo o número de empleado',
            'password' => 'contraseña',
        ]);

        $login = trim((string) $validated['login']);
        $password = (string) $validated['password'];
        $remember = (bool) $validated['remember'];

        $user = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? User::query()->where('email', strtolower($login))->first()
            : User::query()->where('employee_number', $login)->first();

        if ($user !== null && Hash::check($password, (string) $user->password)) {
            if (! $user->is_active) {
                $this->addError('login', 'Tu usuario está desactivado.');
                return null;
            }
            Auth::login($user, $remember);
            request()->session()->regenerate();
            $user->forceFill(['last_login_at' => now()])->save();

            return redirect()->intended('/monitor');
        }

        $this->addError('login', 'Credenciales inválidas.');

        return null;
    }

    public function render(): View
    {
        return view('livewire.auth.login-page')->layout('layouts.auth', ['title' => 'Iniciar sesión']);
    }
}
