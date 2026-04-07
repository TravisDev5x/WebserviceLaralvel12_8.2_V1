<section class="auth-card">
    <h2 style="margin: 0 0 0.35rem;">Registro básico</h2>
    <p class="muted" style="margin: 0 0 1rem;">Crea un usuario para acceder al webservice.</p>

    <form wire:submit="register">
        <div style="margin-bottom: 0.7rem;">
            <label for="name">Nombre completo</label>
            <input id="name" class="input" type="text" wire:model.live="name" autocomplete="name">
            @error('name') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.7rem;">
            <label for="employeeNumber">Número de empleado</label>
            <input id="employeeNumber" class="input" type="text" wire:model.live="employeeNumber" autocomplete="username">
            @error('employeeNumber') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.7rem;">
            <label for="email">Correo electrónico <span class="muted">(opcional)</span></label>
            <input id="email" class="input" type="email" wire:model.live="email" autocomplete="email" placeholder="Si lo deja vacío, se usará un correo interno">
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

        <button class="btn" type="submit" style="width: 100%;">Crear cuenta</button>
    </form>

    <div style="margin-top: 0.9rem; font-size: 0.92rem; text-align: center;">
        <a href="{{ route('login') }}">Ya tengo cuenta</a>
    </div>
</section>
