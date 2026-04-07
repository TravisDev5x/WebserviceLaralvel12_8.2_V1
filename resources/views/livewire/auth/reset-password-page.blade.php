<section class="auth-card">
    <h2 style="margin: 0 0 0.35rem;">Nueva contraseña</h2>
    <p class="muted" style="margin: 0 0 1rem;">Ingresa la nueva contraseña para tu cuenta.</p>

    <form wire:submit="resetPassword">
        <input type="hidden" wire:model="token">

        <div style="margin-bottom: 0.7rem;">
            <label for="email">Correo</label>
            <input id="email" class="input" type="email" wire:model.live="email" autocomplete="email" placeholder="juan.perez@ecd.mx">
            <small class="muted">Correo de la cuenta que recuperarás.</small>
            @error('email') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.7rem;">
            <label for="password">Contraseña</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input id="reset-password" class="input" type="password" wire:model.live="password" autocomplete="new-password" placeholder="••••••••">
                <button type="button" class="btn btn-sm" data-toggle-password="reset-password">Ver</button>
            </div>
            <small class="muted">Nueva contraseña (mínimo 8 caracteres).</small>
            @error('password') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.9rem;">
            <label for="passwordConfirmation">Confirmar contraseña</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input id="reset-password-confirm" class="input" type="password" wire:model.live="passwordConfirmation" autocomplete="new-password" placeholder="••••••••">
                <button type="button" class="btn btn-sm" data-toggle-password="reset-password-confirm">Ver</button>
            </div>
            @error('passwordConfirmation') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <button class="btn" type="submit" style="width: 100%;">Actualizar contraseña</button>
    </form>
</section>
<script>
    (function () {
        document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
            btn.addEventListener('click', function () {
                const input = document.getElementById(this.getAttribute('data-toggle-password'));
                if (!input) return;
                input.type = input.type === 'password' ? 'text' : 'password';
                this.textContent = input.type === 'password' ? 'Ver' : 'Ocultar';
            });
        });
    })();
</script>
