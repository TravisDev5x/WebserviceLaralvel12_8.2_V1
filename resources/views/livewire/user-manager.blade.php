<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Usuarios y roles</h2>
            <p class="page-subtitle">Alta de usuarios, roles y estado (solo administrador).</p>
        </div>
        <button class="btn btn-primary" wire:click="openCreateUser" type="button">Nuevo usuario</button>
    </div>
    @if(session('user_success'))
        <div class="card card-pad" style="margin-bottom:.75rem; border-left:3px solid #22c55e; background:var(--app-row);" role="status">
            <p style="margin:0;">{{ session('user_success') }}</p>
        </div>
    @endif
    @if(session('user_error'))
        <div class="card card-pad" style="margin-bottom:.75rem; border-left:3px solid #dc2626; background:var(--app-row);" role="alert">
            <p style="margin:0;">{{ session('user_error') }}</p>
        </div>
    @endif
    @if(session('user_created'))
        <div class="card card-pad" style="margin-bottom:.75rem; border-left:3px solid #22c55e; background:var(--app-row);">
            <p style="margin:0;">{{ session('user_created') }}</p>
        </div>
    @endif
    <section class="card card-pad">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:.75rem;">
            <div><input class="input" type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, correo o número"><small class="muted">Filtro rápido de usuarios. Ejemplo: <code>18680</code>.</small></div>
            <div>
                <select class="select" wire:model.live="roleFilter">
                    <option value="all">Todos los roles</option>
                    <option value="admin">Administrador</option>
                    <option value="operator">Operador</option>
                    <option value="viewer">Solo lectura</option>
                </select>
                <small class="muted">Administrador=acceso total, Operador=operación diaria, Solo lectura=sin cambios.</small>
            </div>
            <div>
                <select class="select" wire:model.live="statusFilter">
                    <option value="all">Todos</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table-clean" wire:key="users-tbl-{{ $tableVersion }}">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Número empleado</th>
                        <th>Rol</th>
                        <th>Activo</th>
                        <th>Último acceso</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @if($users->count() > 0)
                    @foreach($users as $user)
                        <tr wire:key="u-{{ $user->id }}-v{{ $tableVersion }}">
                            <td>{{ $user->name }}</td>
                            <td>
                                @if($user->usesSyntheticEmail())
                                    <span class="muted" title="Correo interno generado automáticamente">Sin correo</span>
                                @else
                                    {{ $user->email }}
                                @endif
                            </td>
                            <td>{{ $user->employee_number ?? '—' }}</td>
                            <td style="min-width: 11rem;">
                                @if($user->id === auth()->id())
                                    <span class="badge-soft" style="padding:.2rem .5rem;border-radius:999px;font-size:.78rem;">{{ $user->role }}</span>
                                    <p class="muted" style="margin:.25rem 0 0;font-size:.75rem;">Su rol no se cambia aquí</p>
                                @else
                                    <select class="select" style="max-width:100%;font-size:.85rem;padding:.35rem .5rem;"
                                        wire:change="promptRoleChange({{ $user->id }}, $event.target.value, @json($user->role), @json($user->name))">
                                        <option value="admin" @selected($user->role === 'admin')>Administrador</option>
                                        <option value="operator" @selected($user->role === 'operator')>Operador</option>
                                        <option value="viewer" @selected($user->role === 'viewer' || $user->role === null || $user->role === '')>Solo lectura</option>
                                    </select>
                                    <small class="muted" style="display:block;font-size:.75rem;">Administrador: todo | Operador: monitoreo y operación | Visitante: solo lectura</small>
                                @endif
                            </td>
                            <td>
                                @if($user->id === auth()->id())
                                    <span class="muted">—</span>
                                @else
                                    <label class="muted" style="display:inline-flex;align-items:center;gap:.35rem;cursor:pointer;font-size:.85rem;">
                                        <input type="checkbox" @checked($user->is_active) wire:click.prevent="toggleActive({{ $user->id }})" wire:loading.attr="disabled">
                                        {{ $user->is_active ? 'Sí' : 'No' }}
                                    </label>
                                    <small class="muted" style="display:block;font-size:.75rem;">Si se desactiva, el usuario no puede iniciar sesión.</small>
                                @endif
                            </td>
                            <td style="white-space:nowrap;font-size:.85rem;">{{ $user->last_login_at?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td style="white-space:nowrap;font-size:.85rem;">{{ $user->created_at?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td>
                                <button class="btn btn-sm" type="button" wire:click="openEditUser({{ $user->id }})">Editar</button>
                            </td>
                        </tr>
                    @endforeach
                    @else
                        <tr><td colspan="8" class="muted">No hay usuarios que coincidan.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div style="margin-top:.75rem;">{{ $users->links() }}</div>
    </section>

    @if($showRoleConfirmModal)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:60;">
            <div class="card card-pad" style="width:min(95vw, 480px);">
                <h3 style="margin-top:0;">Confirmar cambio de rol</h3>
                <p style="margin:0 0 1rem;line-height:1.5;">¿Cambiar el rol de <strong>{{ $roleModalName }}</strong> de <strong>{{ $roleModalOld }}</strong> a <strong>{{ $roleModalNew }}</strong>?</p>
                <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                    <button class="btn" type="button" wire:click="cancelRoleModal">Cancelar</button>
                    <button class="btn btn-primary" type="button" wire:click="confirmRoleModal">Sí, cambiar</button>
                </div>
            </div>
        </div>
    @endif

    @if($showCreateUserModal)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:60;">
            <div class="card card-pad" style="width:min(95vw, 620px); max-height:90vh; overflow:auto;">
                <h3 style="margin-top:0;">Nuevo usuario</h3>
                <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div><label>Nombre</label><input class="input" type="text" wire:model.live="newUserName" autocomplete="off" placeholder="Juan Pérez García"><small class="muted">Nombre completo del usuario.</small></div>
                    <div><label>Correo <span class="muted">(opcional)</span></label><input class="input" type="email" wire:model.live="newUserEmail" autocomplete="off" placeholder="juan.perez@ecd.mx"><small class="muted">Si se deja vacío, se genera correo interno.</small></div>
                    <div><label>Número empleado</label><input class="input" type="text" wire:model.live="newUserEmployeeNumber" autocomplete="off" placeholder="18680"><small class="muted">Identificador de acceso del usuario.</small></div>
                    <div>
                        <label>Rol inicial</label>
                        <select class="select" wire:model.live="newUserRole">
                            <option value="admin">Administrador</option>
                            <option value="operator">Operador</option>
                            <option value="viewer">Solo lectura</option>
                        </select>
                    </div>
                    <div><label>Contraseña</label><div style="display:flex;gap:.5rem;align-items:center;"><input id="new-user-password" class="input" type="password" wire:model.live="newUserPassword" autocomplete="new-password" placeholder="••••••••"><button type="button" class="btn btn-sm" data-toggle-password="new-user-password">Ver</button></div><small class="muted">Mínimo 8 caracteres con letras y números.</small></div>
                    <div><label>Confirmar contraseña</label><div style="display:flex;gap:.5rem;align-items:center;"><input id="new-user-password-confirm" class="input" type="password" wire:model.live="newUserPasswordConfirmation" autocomplete="new-password" placeholder="••••••••"><button type="button" class="btn btn-sm" data-toggle-password="new-user-password-confirm">Ver</button></div></div>
                </div>
                <div style="margin-top:1rem; display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="closeCreateUser" type="button">Cancelar</button>
                    <button class="btn btn-primary" wire:click="createUser" type="button">Crear usuario</button>
                </div>
            </div>
        </div>
    @endif

    @if($showEditUserModal && $editUserId !== null)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:60;">
            <div class="card card-pad" style="width:min(95vw, 620px); max-height:90vh; overflow:auto;">
                <h3 style="margin-top:0;">Editar usuario</h3>
                <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div><label>Nombre</label><input class="input" type="text" wire:model.live="editUserName" autocomplete="off" placeholder="Juan Pérez García"><small class="muted">Nombre completo del usuario.</small></div>
                    <div><label>Correo <span class="muted">(opcional)</span></label><input class="input" type="email" wire:model.live="editUserEmail" autocomplete="off" placeholder="juan.perez@ecd.mx"><small class="muted">Correo de recuperación de contraseña.</small></div>
                    <div><label>Número empleado</label><input class="input" type="text" wire:model.live="editUserEmployeeNumber" autocomplete="off" placeholder="18680"><small class="muted">Número de empleado para login.</small></div>
                    <div>
                        <label>Rol</label>
                        @if($editUserId === auth()->id())
                            <p class="muted" style="margin:0;font-size:.88rem;">No puede cambiar su propio rol aquí.</p>
                            <input type="hidden" wire:model="editUserRole">
                        @else
                            <select class="select" wire:model.live="editUserRole">
                                <option value="admin">Administrador</option>
                                <option value="operator">Operador</option>
                                <option value="viewer">Solo lectura</option>
                            </select>
                        @endif
                    </div>
                </div>
                <div style="margin-top:1rem; display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="closeEditUser" type="button">Cancelar</button>
                    <button class="btn btn-primary" wire:click="saveEditUser" type="button">Guardar</button>
                </div>
            </div>
        </div>
    @endif
</div>
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
