<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Mi perfil</h2>
            <p class="page-subtitle">Datos de tu cuenta y contraseña. No necesitas entrar al listado de usuarios.</p>
        </div>
    </div>

    @if(session('profile_ok'))
        <section class="card card-pad" style="margin-bottom:1rem; border-left:4px solid #16a34a;">
            <p style="margin:0;">{{ session('profile_ok') }}</p>
        </section>
    @endif
    @if(session('password_ok'))
        <section class="card card-pad" style="margin-bottom:1rem; border-left:4px solid #16a34a;">
            <p style="margin:0;">{{ session('password_ok') }}</p>
        </section>
    @endif

    <section class="card card-pad" style="margin-bottom:1rem;">
        <h3 style="margin:0 0 .75rem; font-size:1rem;">Datos personales</h3>
        <form wire:submit="updateProfile" class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
            <div>
                <label for="profile-name">Nombre</label>
                <input id="profile-name" class="input" type="text" wire:model="name" autocomplete="name">
                @error('name') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="profile-email">Correo</label>
                <input id="profile-email" class="input" type="email" value="{{ $email }}" readonly disabled style="opacity:.85;">
                <p class="muted" style="margin:.25rem 0 0; font-size:.78rem;">El correo solo lo puede cambiar un administrador.</p>
            </div>
            <div>
                <label for="profile-employee">Número de empleado</label>
                <input id="profile-employee" class="input" type="text" value="{{ $employeeNumber }}" readonly disabled style="opacity:.85;">
            </div>
            <div>
                <label for="profile-role">Rol</label>
                <input id="profile-role" class="input" type="text" value="{{ $roleDisplay }}" readonly disabled style="opacity:.85;">
            </div>
            <div style="display:flex; align-items:flex-end;">
                <button class="btn btn-primary" type="submit">Guardar nombre</button>
            </div>
        </form>
    </section>

    <section class="card card-pad">
        <h3 style="margin:0 0 .75rem; font-size:1rem;">Cambiar contraseña</h3>
        <form wire:submit="updatePassword" class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); max-width:640px;">
            <div style="grid-column:1 / -1;">
                <label for="profile-current-pw">Contraseña actual</label>
                <input id="profile-current-pw" class="input" type="password" wire:model="currentPassword" autocomplete="current-password">
                @error('currentPassword') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="profile-new-pw">Nueva contraseña</label>
                <input id="profile-new-pw" class="input" type="password" wire:model="newPassword" autocomplete="new-password">
                @error('newPassword') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="profile-confirm-pw">Confirmar nueva contraseña</label>
                <input id="profile-confirm-pw" class="input" type="password" wire:model="newPasswordConfirmation" autocomplete="new-password">
                @error('newPasswordConfirmation') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div style="grid-column:1 / -1;">
                <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
            </div>
        </form>
    </section>
</div>
