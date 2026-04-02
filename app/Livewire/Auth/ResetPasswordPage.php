<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Component;

class ResetPasswordPage extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public ?string $statusMessage = null;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): mixed
    {
        $validated = $this->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string', 'min:8'],
        ], [], [
            'email' => 'correo',
            'password' => 'contraseña',
        ]);

        $status = Password::reset([
            'email' => strtolower(trim((string) $validated['email'])),
            'token' => (string) $validated['token'],
            'password' => (string) $validated['password'],
            'password_confirmation' => (string) $validated['passwordConfirmation'],
        ], function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        $this->addError('email', __($status));

        return null;
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password-page')->layout('layouts.auth', ['title' => 'Nueva contraseña']);
    }
}
