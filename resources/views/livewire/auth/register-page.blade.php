<section class="auth-card">
    <h2 style="margin: 0 0 0.35rem;">Registro básico</h2>
    <p class="muted" style="margin: 0 0 1rem;">Crea un usuario para acceder al webservice.</p>

    <form wire:submit="register">
        <div style="margin-bottom: 0.7rem;">
            <label for="name">Nombre completo</label>
            <input id="name" class="input" type="text" wire:model.live="name" autocomplete="name" placeholder="Juan Pérez García">
            <small class="muted">Tu nombre completo como aparece en tu credencial de empleado.</small>
            @error('name') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.7rem;">
            <label for="employeeNumber">Número de empleado</label>
            <input id="employeeNumber" class="input" type="text" wire:model.live="employeeNumber" autocomplete="username" placeholder="18680">
            <small class="muted">Número de empleado ECD. También se usa para iniciar sesión. Ejemplo: <code>18680</code>.</small>
            @error('employeeNumber') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.7rem;">
            <label for="email">Correo electrónico <span class="muted">(opcional)</span></label>
            <input id="email" class="input" type="email" wire:model.live="email" autocomplete="email" placeholder="juan.perez@ecd.mx">
            <small class="muted">Opcional. Si lo agregas, puedes recuperar contraseña por correo.</small>
            @error('email') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.7rem;">
            <label for="password">Contraseña</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input id="register-password" class="input" type="password" wire:model.live="password" autocomplete="new-password" placeholder="••••••••">
                <button type="button" class="btn btn-sm" data-toggle-password="register-password">Ver</button>
            </div>
            <small class="muted">Mínimo 8 caracteres, combina letras y números.</small>
            @error('password') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.9rem;">
            <label for="passwordConfirmation">Confirmar contraseña</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input id="register-password-confirm" class="input" type="password" wire:model.live="passwordConfirmation" autocomplete="new-password" placeholder="••••••••">
                <button type="button" class="btn btn-sm" data-toggle-password="register-password-confirm">Ver</button>
            </div>
            @error('passwordConfirmation') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <button class="btn" type="submit" style="width: 100%;">Crear cuenta</button>
    </form>

    <div style="margin-top: 0.9rem; font-size: 0.92rem; text-align: center;">
        <a href="{{ route('login') }}">Ya tengo cuenta</a>
    </div>
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
