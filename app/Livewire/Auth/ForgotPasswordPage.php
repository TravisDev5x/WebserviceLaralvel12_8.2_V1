<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ForgotPasswordPage extends Component
{
    public string $login = '';

    public ?string $statusMessage = null;

    public function sendResetLink(): void
    {
        $validated = $this->validate([
            'login' => ['required', 'string', 'max:255'],
        ], [], [
            'login' => 'correo o número de empleado',
        ]);

        $email = $this->resolveEmail(trim((string) $validated['login']));
        if ($email === null) {
            $this->addError('login', 'No encontramos una cuenta asociada.');

            return;
        }

        $status = Password::sendResetLink(['email' => $email]);
        if ($status === Password::RESET_LINK_SENT) {
            $this->statusMessage = __($status);
            $this->resetErrorBag();

            return;
        }

        $this->addError('login', __($status));
    }

    private function resolveEmail(string $login): ?string
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return strtolower($login);
        }

        return User::query()->where('employee_number', $login)->value('email');
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password-page')->layout('layouts.auth', ['title' => 'Recuperar contraseña']);
    }
}
