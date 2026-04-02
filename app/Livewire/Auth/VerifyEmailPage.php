<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class VerifyEmailPage extends Component
{
    public ?string $statusMessage = null;

    public function resend(): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->addError('general', 'Sesión no válida.');

            return;
        }

        if ($user->hasVerifiedEmail()) {
            $this->redirect('/monitor', navigate: true);

            return;
        }

        $user->sendEmailVerificationNotification();
        $this->statusMessage = 'Enlace de verificación reenviado al correo registrado.';
    }

    public function render(): View
    {
        return view('livewire.auth.verify-email-page')->layout('layouts.auth', ['title' => 'Verificar correo']);
    }
}
