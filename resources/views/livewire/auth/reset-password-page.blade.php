<section class="auth-card">
    <h2 style="margin: 0 0 0.35rem;">Nueva contraseña</h2>
    <p class="muted" style="margin: 0 0 1rem;">Ingresa la nueva contraseña para tu cuenta.</p>

    <form wire:submit="resetPassword">
        <input type="hidden" wire:model="token">

        <div style="margin-bottom: 0.7rem;">
            <label for="email">Correo</label>
            <input id="email" class="input" type="email" wire:model.live="email" autocomplete="email">
            @error('email') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.7rem;">
            <label for="password">Contraseña</label>
            <input id="password" class="input" type="password" wire:model.live="password" autocomplete="new-password">
            @error('password') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.9rem;">
            <label for="passwordConfirmation">Confirmar contraseña</label>
            <input id="passwordConfirmation" class="input" type="password" wire:model.live="passwordConfirmation" autocomplete="new-password">
            @error('passwordConfirmation') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <button class="btn" type="submit" style="width: 100%;">Actualizar contraseña</button>
    </form>
</section>
