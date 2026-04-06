<div class="profile-page">
    <style>
        .profile-page { --profile-accent: #2563eb; }
        html[data-theme="light"] .profile-page { --profile-accent: #1d4ed8; }
        .profile-section {
            margin-bottom: 1.1rem;
            overflow: hidden;
        }
        .profile-section-head {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin: -0.15rem 0 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid var(--app-border);
        }
        .profile-section-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 0.55rem;
            background: var(--app-row);
            border: 1px solid var(--app-border);
            flex-shrink: 0;
        }
        .profile-section-icon i {
            width: 1.1rem;
            height: 1.1rem;
            color: var(--profile-accent);
        }
        .profile-section-head h3 {
            margin: 0;
            font-size: 1.05rem;
            line-height: 1.25;
        }
        .profile-section-desc {
            margin: 0.28rem 0 0;
            font-size: 0.82rem;
            line-height: 1.4;
        }
        .profile-alert {
            margin-bottom: 1rem;
            padding: 0.72rem 0.95rem;
            border-radius: 0.55rem;
            border: 1px solid var(--app-border);
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
            font-size: 0.88rem;
        }
        .profile-alert--ok {
            background: color-mix(in srgb, #16a34a 12%, transparent);
            border-color: color-mix(in srgb, #16a34a 35%, var(--app-border));
        }
        .profile-alert--ok i { width: 1rem; height: 1rem; color: #16a34a; flex-shrink: 0; margin-top: 0.12rem; }
        .profile-layout {
            display: grid;
            gap: 1.15rem;
            grid-template-columns: 1fr;
        }
        @media (min-width: 768px) {
            .profile-layout--split {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                align-items: start;
            }
        }
        .profile-readonly-stack {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }
        .profile-kv {
            display: grid;
            gap: 0.2rem;
            padding: 0.55rem 0.72rem;
            border-radius: 0.5rem;
            background: var(--app-row);
            border: 1px solid var(--app-border);
        }
        .profile-kv-label {
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--app-muted);
        }
        .profile-kv-value {
            font-size: 0.9rem;
            font-weight: 600;
            word-break: break-word;
        }
        .profile-kv-hint {
            font-size: 0.75rem;
            color: var(--app-muted);
            margin-top: 0.15rem;
            line-height: 1.35;
        }
        .profile-edit-block label {
            display: block;
            margin-bottom: 0.35rem;
            font-weight: 600;
            font-size: 0.84rem;
        }
        .profile-edit-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            align-items: flex-end;
        }
        .profile-edit-row .input {
            flex: 1 1 200px;
            min-width: 0;
            max-width: 100%;
        }
        .profile-password-form {
            max-width: 28rem;
        }
        .profile-password-note {
            font-size: 0.78rem;
            color: var(--app-muted);
            margin: 0 0 0.85rem;
            line-height: 1.45;
        }
        .profile-actions-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            margin-top: 0.25rem;
        }
        .profile-actions-bar .btn { min-height: 2.4rem; }
    </style>

    <div class="page-header">
        <div>
            <h2 class="page-title">Mi perfil</h2>
            <p class="page-subtitle">Datos de tu cuenta y seguridad. Puedes editarlos aquí sin usar el listado de usuarios.</p>
        </div>
    </div>

    @if(session('profile_ok'))
        <div class="profile-alert profile-alert--ok" role="status">
            <i data-lucide="check-circle"></i>
            <span>{{ session('profile_ok') }}</span>
        </div>
    @endif
    @if(session('password_ok'))
        <div class="profile-alert profile-alert--ok" role="status">
            <i data-lucide="check-circle"></i>
            <span>{{ session('password_ok') }}</span>
        </div>
    @endif

    <section class="card card-pad profile-section">
        <header class="profile-section-head">
            <span class="profile-section-icon" aria-hidden="true"><i data-lucide="user"></i></span>
            <div>
                <h3>Datos personales</h3>
                <p class="muted profile-section-desc">Tu nombre es el único dato editable en esta pantalla. Correo, número de empleado y rol los gestiona un administrador.</p>
            </div>
        </header>
        <div class="profile-layout profile-layout--split">
            <form wire:submit="updateProfile" class="profile-edit-block">
                <label for="profile-name">Nombre para mostrar</label>
                <div class="profile-edit-row">
                    <input id="profile-name" class="input" type="text" wire:model="name" autocomplete="name" placeholder="Tu nombre en el sistema">
                    <button class="btn btn-primary" type="submit">Guardar nombre</button>
                </div>
                @error('name') <small style="color:#dc2626; display:block; margin-top:.35rem;">{{ $message }}</small> @enderror
            </form>
            <div class="profile-readonly-stack" aria-label="Datos de cuenta de solo lectura">
                <div class="profile-kv">
                    <span class="profile-kv-label">Correo</span>
                    <span class="profile-kv-value">{{ $email }}</span>
                    <p class="profile-kv-hint">Solo un administrador puede cambiar el correo asociado a tu cuenta.</p>
                </div>
                <div class="profile-kv">
                    <span class="profile-kv-label">Número de empleado</span>
                    <span class="profile-kv-value">{{ $employeeNumber !== '' ? $employeeNumber : '—' }}</span>
                </div>
                <div class="profile-kv">
                    <span class="profile-kv-label">Rol</span>
                    <span class="profile-kv-value">{{ $roleDisplay }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="card card-pad profile-section">
        <header class="profile-section-head">
            <span class="profile-section-icon" aria-hidden="true"><i data-lucide="key-round"></i></span>
            <div>
                <h3>Seguridad</h3>
                <p class="muted profile-section-desc">Usa una contraseña larga y distinta de otros sitios. Tras cambiarla, sigue usando tu correo o número de empleado para entrar.</p>
            </div>
        </header>
        <form wire:submit="updatePassword" class="profile-password-form">
            <p class="profile-password-note">Debes escribir tu contraseña actual para confirmar el cambio.</p>
            <div class="grid gap-3" style="grid-template-columns: 1fr;">
                <div>
                    <label for="profile-current-pw">Contraseña actual</label>
                    <input id="profile-current-pw" class="input" type="password" wire:model="currentPassword" autocomplete="current-password" style="width:100%;">
                    @error('currentPassword') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label for="profile-new-pw">Nueva contraseña</label>
                    <input id="profile-new-pw" class="input" type="password" wire:model="newPassword" autocomplete="new-password" style="width:100%;" placeholder="Mínimo 8 caracteres">
                    @error('newPassword') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label for="profile-confirm-pw">Confirmar nueva contraseña</label>
                    <input id="profile-confirm-pw" class="input" type="password" wire:model="newPasswordConfirmation" autocomplete="new-password" style="width:100%;">
                    @error('newPasswordConfirmation') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
                <div class="profile-actions-bar">
                    <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
                </div>
            </div>
        </form>
    </section>
</div>
