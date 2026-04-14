<div class="acm-page">
    <style>
        .acm-page .acm-users-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .85rem;
        }
        .acm-page .acm-users-head-main {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .55rem .85rem;
            min-width: 0;
        }
        .acm-page .acm-users-head-main h3 {
            margin: 0;
            line-height: 1.2;
            font-size: 1rem;
        }
        .acm-page .acm-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }
        .acm-page .acm-filters-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr 1fr .75fr auto;
            gap: .6rem .75rem;
            align-items: end;
            margin-bottom: .8rem;
        }
        .acm-page .acm-filter-item {
            min-width: 0;
        }
        .acm-page .acm-filter-item label {
            display: block;
            font-size: .72rem;
            margin-bottom: .25rem;
            color: var(--app-muted);
        }
        .acm-page .acm-help {
            display: block;
            margin-top: .28rem;
            font-size: .74rem;
            color: var(--app-muted);
            line-height: 1.35;
        }
        .acm-page .acm-clear-wrap {
            display: flex;
            align-items: end;
            justify-content: flex-end;
            min-width: 9.5rem;
        }
        .acm-page .acm-table-actions {
            display: flex;
            gap: .3rem;
            flex-wrap: nowrap;
            justify-content: flex-start;
        }
        .acm-page .acm-table-actions .btn {
            width: 2rem;
            min-width: 2rem;
            padding: .35rem .3rem;
            justify-content: center;
        }
        .acm-page .acm-role-select {
            min-width: 11rem;
            max-width: 14rem;
        }
        .acm-page .acm-modal-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }
        .acm-page .acm-modal-grid .full {
            grid-column: 1 / -1;
        }
        @media (max-width: 980px) {
            .acm-page .acm-filters-grid {
                grid-template-columns: 1fr 1fr;
            }
            .acm-page .acm-clear-wrap {
                grid-column: 1 / -1;
                justify-content: stretch;
            }
            .acm-page .acm-clear-wrap .btn {
                width: 100%;
            }
        }
        @media (max-width: 720px) {
            .acm-page .acm-filters-grid {
                grid-template-columns: 1fr;
            }
            .acm-page .acm-table-actions {
                flex-wrap: wrap;
            }
            .acm-page .acm-modal-grid {
                grid-template-columns: 1fr;
            }
            .acm-page .acm-role-select {
                min-width: 0;
                max-width: 100%;
            }
        }
    </style>
    <div class="page-header">
        <div>
            <h2 class="page-title">Usuarios y catálogo de roles</h2>
            <p class="page-subtitle">Gestión dinámica de permisos por rol.</p>
        </div>
    </div>

    @if(session('user_created'))
        <div class="alert mb-3" role="status">
            <h2 class="text-sm font-semibold m-0">{{ session('user_created') }}</h2>
        </div>
    @endif

    <div class="card card-pad users-list-section" style="margin-bottom:1rem;">
        <div class="acm-users-head">
            <div class="acm-users-head-main">
                <h3>Listado de usuarios</h3>
                <div class="acm-badges">
                    <span class="badge-soft">Total: {{ $users->total() }}</span>
                    <span class="badge-soft">Pág. {{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
                    @if($userSearch !== '')
                        <span class="badge-soft">"{{ $userSearch }}"</span>
                    @endif
                </div>
            </div>
            <button class="btn btn-primary" wire:click="openCreateUser" type="button" data-tooltip="Registrar un nuevo usuario">
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><x-svg-lucide name="user-plus" class="size-4 shrink-0" aria-hidden="true" />Nuevo usuario</span>
            </button>
        </div>
        <div class="acm-filters-grid">
            <div class="acm-filter-item">
                <label>Buscar</label>
                <input class="input" type="text" wire:model.live.debounce.300ms="userSearch" placeholder="Nombre, email o número…" style="width:100%;">
                <small class="acm-help">Filtra por nombre, correo o número de empleado. Ejemplo: <code>18680</code>.</small>
            </div>
            <div class="acm-filter-item">
                <label>Rol</label>
                <select class="select" wire:model.live="userRoleFilter">
                    <option value="all">Todos los roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->slug }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                <small class="acm-help">Administrador = total, Operador = operación, Visitante = lectura.</small>
            </div>
            <div class="acm-filter-item">
                <label>Estado</label>
                <select class="select" wire:model.live="userStatusFilter">
                    <option value="all">Todos</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                    <option value="deleted">Dados de baja</option>
                </select>
                <small class="acm-help">Cuenta activa, inactiva o dada de baja.</small>
            </div>
            <div class="acm-filter-item">
                <label>Por página</label>
                <select class="select" wire:model.live="usersPerPage">
                    <option value="10">10</option>
                    <option value="12">12</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
                <small class="acm-help">Cantidad de filas por página.</small>
            </div>
            <div class="acm-clear-wrap">
                <button class="btn" wire:click="clearUserFilters" type="button" data-tooltip="Limpiar todos los filtros">
                    <span style="display:inline-flex; align-items:center; gap:.35rem;"><x-svg-lucide name="eraser" class="size-4 shrink-0" aria-hidden="true" />Limpiar filtros</span>
                </button>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">
                            <button type="button" class="th-sort-btn" wire:click="sortUsersBy('name')" title="Ordenar por nombre">
                                Nombre
                                @if($userSortBy === 'name')
                                    <span aria-hidden="true">{{ $userSortDir === 'asc' ? ' ↑' : ' ↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col">
                            <button type="button" class="th-sort-btn" wire:click="sortUsersBy('email')" title="Ordenar por correo">
                                Email
                                @if($userSortBy === 'email')
                                    <span aria-hidden="true">{{ $userSortDir === 'asc' ? ' ↑' : ' ↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col">
                            <button type="button" class="th-sort-btn" wire:click="sortUsersBy('employee_number')" title="Ordenar por número">
                                Número
                                @if($userSortBy === 'employee_number')
                                    <span aria-hidden="true">{{ $userSortDir === 'asc' ? ' ↑' : ' ↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col">Estado</th>
                        <th scope="col">
                            <button type="button" class="th-sort-btn" wire:click="sortUsersBy('role')" title="Ordenar por rol">
                                Rol
                                @if($userSortBy === 'role')
                                    <span aria-hidden="true">{{ $userSortDir === 'asc' ? ' ↑' : ' ↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @if($users->count() > 0)
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>
                            <div style="display:flex; align-items:center; gap:.55rem;">
                                <span class="badge-soft" style="min-width:2rem; justify-content:center;">{{ strtoupper(substr((string) $user->name, 0, 1)) }}</span>
                                <span>{{ $user->name }}</span>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->employee_number }}</td>
                        <td>
                            <span class="badge-soft" style="{{ $user->trashed() ? 'background:#e5e7eb;color:#374151;' : ($user->is_active ? 'background:#d1fae5;color:#065f46;' : 'background:#fee2e2;color:#991b1b;') }}">
                                {{ $user->trashed() ? 'Dado de baja' : ($user->is_active ? 'Activo' : 'Inactivo') }}
                            </span>
                        </td>
                        <td>
                            <select class="select acm-role-select" wire:change="assignRole({{ $user->id }}, $event.target.value)">
                                @foreach($roles as $role)
                                    <option value="{{ $role->slug }}" @selected($user->role === $role->slug)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <div class="acm-table-actions">
                                <button class="btn btn-sm" wire:click="editUser({{ $user->id }})" type="button" data-tooltip="Editar datos del usuario" aria-label="Editar usuario">
                                    <span style="display:inline-flex; align-items:center;"><x-svg-lucide name="pencil" class="size-4 shrink-0" aria-hidden="true" /></span>
                                </button>
                                <button class="btn btn-sm" wire:click="openPasswordEditor({{ $user->id }})" type="button" data-tooltip="Cambiar contraseña" aria-label="Cambiar contraseña">
                                    <span style="display:inline-flex; align-items:center;"><x-svg-lucide name="key-round" class="size-4 shrink-0" aria-hidden="true" /></span>
                                </button>
                                @if(! $user->trashed())
                                    <button class="btn btn-sm" wire:click="toggleUserStatus({{ $user->id }})" type="button" data-tooltip="{{ $user->is_active ? 'Desactivar temporalmente' : 'Activar temporalmente' }}" aria-label="{{ $user->is_active ? 'Desactivar temporalmente' : 'Activar temporalmente' }}">
                                        <span style="display:inline-flex; align-items:center;">@if($user->is_active)<x-svg-lucide name="pause-circle" class="size-4 shrink-0" aria-hidden="true" />@else<x-svg-lucide name="play-circle" class="size-4 shrink-0" aria-hidden="true" />@endif</span>
                                    </button>
                                    <button class="btn btn-sm btn-destructive" wire:click="askDeactivateUser({{ $user->id }})" type="button" data-tooltip="Dar de baja (soft delete)" aria-label="Dar de baja">
                                        <span style="display:inline-flex; align-items:center;"><x-svg-lucide name="user-x" class="size-4 shrink-0" aria-hidden="true" /></span>
                                    </button>
                                @else
                                    <button class="btn btn-sm" wire:click="askRestoreUser({{ $user->id }})" type="button" data-tooltip="Restaurar usuario" aria-label="Restaurar usuario">
                                        <span style="display:inline-flex; align-items:center;"><x-svg-lucide name="rotate-ccw" class="size-4 shrink-0" aria-hidden="true" /></span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                @else
                    <tr>
                        <td colspan="7">
                            <div style="padding:.75rem 0;">
                                No hay usuarios para los filtros aplicados.
                                <button class="btn btn-sm" wire:click="clearUserFilters" type="button">Limpiar filtros</button>
                            </div>
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
        <div style="margin-top:.75rem;">{{ $users->links() }}</div>
    </div>

    @if($showCreateUserModal)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:60;">
            <div class="card card-pad" style="width:min(95vw, 620px); max-height:90vh; overflow:auto;">
                <h3 style="margin-top:0;">Nuevo usuario</h3>
                <div class="acm-modal-grid">
                    <div><label>Nombre</label><input class="input" type="text" wire:model.live="createUserName" autocomplete="off" placeholder="Juan Pérez García"><small class="muted">Nombre completo del nuevo usuario.</small></div>
                    <div><label>Correo <span class="muted">(opcional)</span></label><input class="input" type="email" wire:model.live="createUserEmail" autocomplete="off" placeholder="juan.perez@ecd.mx"><small class="muted">Correo para recuperación de contraseña.</small></div>
                    <div><label>Número empleado</label><input class="input" type="text" wire:model.live="createUserEmployeeNumber" autocomplete="off" placeholder="18680"><small class="muted">Número interno que también sirve para login.</small></div>
                    <div>
                        <label>Rol inicial</label>
                        <select class="select" wire:model.live="createUserRole">
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}">{{ $role->name }} ({{ $role->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label>Contraseña</label><div style="display:flex;gap:.5rem;align-items:center;"><input id="ac-create-password" class="input" type="password" wire:model.live="createUserPassword" autocomplete="new-password" placeholder="••••••••"><button type="button" class="btn btn-sm" data-toggle-password="ac-create-password">Ver</button></div><small class="muted">Mínimo 8 caracteres.</small></div>
                    <div><label>Confirmar contraseña</label><div style="display:flex;gap:.5rem;align-items:center;"><input id="ac-create-password-confirm" class="input" type="password" wire:model.live="createUserPasswordConfirmation" autocomplete="new-password" placeholder="••••••••"><button type="button" class="btn btn-sm" data-toggle-password="ac-create-password-confirm">Ver</button></div></div>
                </div>
                <div style="margin-top:1rem; display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="closeCreateUser" type="button">Cancelar</button>
                    <button class="btn btn-primary" wire:click="createUser" type="button">Crear usuario</button>
                </div>
            </div>
        </div>
    @endif

    @if($showUserEditor)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:60;">
            <div class="card card-pad" style="width:min(95vw, 620px);">
                <h3 style="margin-top:0;">Editar usuario</h3>
                <div class="acm-modal-grid">
                    <div><label>Nombre</label><input class="input" type="text" wire:model.live="editUserName" placeholder="Juan Pérez García"><small class="muted">Nombre visible del usuario.</small></div>
                    <div><label>Correo <span class="muted">(opcional)</span></label><input class="input" type="email" wire:model.live="editUserEmail" placeholder="juan.perez@ecd.mx"><small class="muted">Correo de acceso/recuperación.</small></div>
                    <div><label>Número empleado</label><input class="input" type="text" wire:model.live="editUserEmployeeNumber" placeholder="18680"><small class="muted">Identificador interno del usuario.</small></div>
                    <div>
                        <label>Rol</label>
                        <select class="select" wire:model.live="editUserRole">
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="margin-top:.75rem;">
                    <label style="display:inline-flex; gap:.35rem; align-items:center;"><input type="checkbox" wire:model.live="editUserIsActive"> Activo</label>
                    <small class="muted" style="display:block;">Si se desactiva, no podrá iniciar sesión.</small>
                </div>
                <div style="margin-top:1rem; display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="closeUserEditor" type="button">Cancelar</button>
                    <button class="btn btn-primary" wire:click="saveUser" type="button">Guardar cambios</button>
                </div>
            </div>
        </div>
    @endif

    @if($showPasswordEditor)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:60;">
            <div class="card card-pad" style="width:min(95vw, 520px);">
                <h3 style="margin-top:0;">Cambiar contraseña</h3>
                <div class="grid gap-3">
                    <div><label>Nueva contraseña</label><div style="display:flex;gap:.5rem;align-items:center;"><input id="ac-reset-password" class="input" type="password" wire:model.live="newPassword" placeholder="••••••••"><button type="button" class="btn btn-sm" data-toggle-password="ac-reset-password">Ver</button></div><small class="muted">Contraseña temporal o final para el usuario.</small></div>
                    <div><label>Confirmar contraseña</label><div style="display:flex;gap:.5rem;align-items:center;"><input id="ac-reset-password-confirm" class="input" type="password" wire:model.live="newPasswordConfirmation" placeholder="••••••••"><button type="button" class="btn btn-sm" data-toggle-password="ac-reset-password-confirm">Ver</button></div></div>
                </div>
                <div style="margin-top:1rem; display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="closePasswordEditor" type="button">Cancelar</button>
                    <button class="btn btn-primary" wire:click="saveUserPassword" type="button">Actualizar contraseña</button>
                </div>
            </div>
        </div>
    @endif

    @if($showConfirmActionModal)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:60;">
            <div class="card card-pad" style="width:min(95vw, 520px);">
                <h3 style="margin-top:0;">
                    {{ $confirmAction === 'restore' ? 'Confirmar restauración' : 'Confirmar baja' }}
                </h3>
                <p style="margin:.35rem 0 0;">
                    @if($confirmAction === 'restore')
                        ¿Seguro que deseas restaurar este usuario? Volverá a estar activo.
                    @else
                        ¿Seguro que deseas dar de baja este usuario? Se aplicará soft delete.
                    @endif
                </p>
                <div style="margin-top:1rem; display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="cancelUserAction" type="button">Cancelar</button>
                    <button class="btn {{ $confirmAction === 'restore' ? '' : 'btn-destructive' }}" wire:click="confirmUserAction" type="button">
                        {{ $confirmAction === 'restore' ? 'Sí, restaurar' : 'Sí, dar de baja' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="card card-pad">
        <div class="page-header" style="margin-bottom:.75rem;">
            <h3 class="page-title">Catálogo de roles y permisos</h3>
            <button class="btn" wire:click="newRole" type="button" data-tooltip="Crear nuevo rol">
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><x-svg-lucide name="shield-plus" class="size-4 shrink-0" aria-hidden="true" />Nuevo rol</span>
            </button>
        </div>

        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div><label>Nombre</label><input class="input" type="text" wire:model.live="roleName" placeholder="Supervisor de operación"><small class="muted">Nombre visible del rol.</small></div>
            <div><label>Slug</label><input class="input" type="text" wire:model.live="roleSlug" placeholder="supervisor-operacion"><small class="muted">Identificador técnico sin espacios.</small></div>
            <div style="grid-column:1 / -1;"><label>Descripción</label><input class="input" type="text" wire:model.live="roleDescription" placeholder="Puede monitorear y administrar reglas operativas"><small class="muted">Resumen del alcance de este rol.</small></div>
        </div>

        <div style="margin-top:.75rem;">
            <p style="margin:.35rem 0;">Permisos</p>
            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                <label style="display:inline-flex; gap:.35rem; align-items:center;"><input type="checkbox" value="*" wire:model.live="selectedPermissions"> {{ $permissionLabels['*'] ?? '*' }}</label>
                @foreach($permissionsCatalog as $permission)
                    <label style="display:inline-flex; gap:.35rem; align-items:center;"><input type="checkbox" value="{{ $permission }}" wire:model.live="selectedPermissions"> {{ $permissionLabels[$permission] ?? $permission }}</label>
                @endforeach
            </div>
            @error('selectedPermissions') <small style="color:#dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-top:.75rem; display:flex; gap:.75rem; align-items:center;">
            <label style="display:inline-flex; gap:.35rem; align-items:center;"><input type="checkbox" wire:model.live="roleIsActive"> Rol activo</label>
            <button class="btn btn-primary" wire:click="saveRole" type="button" data-tooltip="Guardar cambios del rol">
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><x-svg-lucide name="save" class="size-4 shrink-0" aria-hidden="true" />Guardar rol</span>
            </button>
        </div>

        <div class="table-wrap" style="margin-top:1rem;">
            <table class="table-clean">
                <thead><tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Permisos</th><th>Activo</th><th>Acciones</th></tr></thead>
                <tbody>
                @if($roles->count() > 0)
                @foreach($roles as $role)
                    <tr>
                        <td>{{ $role->id }}</td>
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->slug }}</td>
                        <td>{{ $role->description }}</td>
                        <td>{{ $role->is_active ? 'Sí' : 'No' }}</td>
                        <td><button class="btn" wire:click="editRole({{ $role->id }})" type="button" data-tooltip="Editar rol" aria-label="Editar rol"><span style="display:inline-flex; align-items:center;"><x-svg-lucide name="pencil" class="size-4 shrink-0" aria-hidden="true" /></span></button></td>
                    </tr>
                @endforeach
                @else
                    <tr><td colspan="6">No hay roles.</td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
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
