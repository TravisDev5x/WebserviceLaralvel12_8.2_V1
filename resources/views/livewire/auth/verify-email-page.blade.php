<section class="auth-card">
    <h2 style="margin: 0 0 0.35rem;">Verifica tu correo</h2>
    <p class="muted" style="margin: 0 0 1rem;">Te enviamos un enlace de verificación. Debes confirmar tu cuenta para acceder al monitor.</p>

    @if ($statusMessage)
        <div class="badge-soft" style="margin-bottom: 0.8rem;">{{ $statusMessage }}</div>
    @endif

    @error('general')
        <small style="color: #dc2626; display: block; margin-bottom: 0.8rem;">{{ $message }}</small>
    @enderror

    <button class="btn" type="button" wire:click="resend" style="width: 100%; margin-bottom: 0.6rem;">Reenviar correo de verificación</button>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn" type="submit" style="width: 100%;">Cerrar sesión</button>
    </form>
</section>
