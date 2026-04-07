<section class="auth-card">
    <h2 style="margin: 0 0 0.35rem;">Recuperar contraseña</h2>
    <p class="muted" style="margin: 0 0 1rem;">Te enviaremos un enlace de recuperación por correo.</p>

    @if ($statusMessage)
        <div class="badge-soft" style="margin-bottom: 0.8rem;">{{ $statusMessage }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" wire:submit="sendResetLink">
        @csrf
        <div style="margin-bottom: 0.9rem;">
            <label for="login">Correo o número de empleado</label>
            <input id="login" name="login" class="input" type="text" wire:model.live="login" autocomplete="username" placeholder="tu@correo.com o 18680">
            <small class="muted">Ingresa correo o número de empleado para buscar tu cuenta.</small>
            @error('login') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>
        <button class="btn" type="submit" style="width: 100%;">Enviar enlace</button>
    </form>

    <div style="margin-top: 0.9rem; font-size: 0.92rem; text-align: center;">
        <a href="{{ route('login') }}">Volver a iniciar sesión</a>
    </div>
</section>
