<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Usuarios y catálogo de roles</h2>
            <p class="page-subtitle">Gestión dinámica de permisos por rol.</p>
        </div>
    </div>

    @if(session('user_created'))
        <div class="card card-pad" style="margin-bottom:.75rem; border-left:3px solid #22c55e; background:#f0fdf4;">
            <p style="margin:0;">{{ session('user_created') }}</p>
        </div>
    @endif

    <section class="card card-pad" style="margin-bottom:1rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; margin-bottom:.75rem;">
            <h3 style="margin:0;">Listado de usuarios</h3>
            <button class="btn btn-primary" wire:click="openCreateUser" type="button" data-tooltip="Registrar un nuevo usuario">
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><i data-lucide="user-plus"></i>Nuevo usuario</span>
            </button>
        </div>
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:.75rem;">
            <div><input class="input" type="text" wire:model.live.debounce.300ms="userSearch" placeholder="Buscar usuario"></div>
            <div>
                <select class="select" wire:model.live="userRoleFilter">
                    <option value="all">Todos los roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->slug }}">{{ $role->name }} ({{ $role->slug }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select class="select" wire:model.live="userStatusFilter">
                    <option value="all">Todos</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                    <option value="deleted">Dados de baja</option>
                </select>
            </div>
            <div>
                <select class="select" wire:model.live="usersPerPage">
                    <option value="10">10 por página</option>
                    <option value="12">12 por página</option>
                    <option value="20">20 por página</option>
                    <option value="50">50 por página</option>
                </select>
            </div>
            <div>
                <button class="btn" wire:click="clearUserFilters" type="button" data-tooltip="Limpiar todos los filtros">
                    <span style="display:inline-flex; align-items:center; gap:.35rem;"><i data-lucide="eraser"></i>Limpiar filtros</span>
                </button>
            </div>
        </div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.75rem;">
            <span class="badge-soft">Total: {{ $users->total() }}</span>
            <span class="badge-soft">Página {{ $users->currentPage() }} de {{ $users->lastPage() }}</span>
            @if($userSearch !== '')
                <span class="badge-soft">Búsqueda: "{{ $userSearch }}"</span>
            @endif
        </div>
        <div class="table-wrap">
            <table class="table-clean">
                <thead><tr><th>ID</th><th><button class="btn btn-ghost" wire:click="sortUsersBy('name')" type="button" data-tooltip="Ordenar por nombre">Nombre</button></th><th><button class="btn btn-ghost" wire:click="sortUsersBy('email')" type="button" data-tooltip="Ordenar por correo">Email</button></th><th><button class="btn btn-ghost" wire:click="sortUsersBy('employee_number')" type="button" data-tooltip="Ordenar por número">Número</button></th><th>Estado</th><th><button class="btn btn-ghost" wire:click="sortUsersBy('role')" type="button" data-tooltip="Ordenar por rol">Rol</button></th><th>Acciones</th></tr></thead>
                <tbody>
                @forelse($users as $user)
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
                            <select class="select" wire:change="assignRole({{ $user->id }}, $event.target.value)">
                                @foreach($roles as $role)
                                    <option value="{{ $role->slug }}" @selected($user->role === $role->slug)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <div style="display:flex; gap:.35rem; flex-wrap:wrap;">
                                <button class="btn btn-sm" wire:click="editUser({{ $user->id }})" type="button" data-tooltip="Editar datos del usuario" aria-label="Editar usuario">
                                    <span style="display:inline-flex; align-items:center;"><i data-lucide="pencil"></i></span>
                                </button>
                                <button class="btn btn-sm" wire:click="openPasswordEditor({{ $user->id }})" type="button" data-tooltip="Cambiar contraseña" aria-label="Cambiar contraseña">
                                    <span style="display:inline-flex; align-items:center;"><i data-lucide="key-round"></i></span>
                                </button>
                                @if(! $user->trashed())
                                    <button class="btn btn-sm" wire:click="toggleUserStatus({{ $user->id }})" type="button" data-tooltip="{{ $user->is_active ? 'Desactivar temporalmente' : 'Activar temporalmente' }}" aria-label="{{ $user->is_active ? 'Desactivar temporalmente' : 'Activar temporalmente' }}">
                                        <span style="display:inline-flex; align-items:center;"><i data-lucide="{{ $user->is_active ? 'pause-circle' : 'play-circle' }}"></i></span>
                                    </button>
                                    <button class="btn btn-sm btn-danger" wire:click="askDeactivateUser({{ $user->id }})" type="button" data-tooltip="Dar de baja (soft delete)" aria-label="Dar de baja">
                                        <span style="display:inline-flex; align-items:center;"><i data-lucide="user-x"></i></span>
                                    </button>
                                @else
                                    <button class="btn btn-sm" wire:click="askRestoreUser({{ $user->id }})" type="button" data-tooltip="Restaurar usuario" aria-label="Restaurar usuario">
                                        <span style="display:inline-flex; align-items:center;"><i data-lucide="rotate-ccw"></i></span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div style="padding:.75rem 0;">
                                No hay usuarios para los filtros aplicados.
                                <button class="btn btn-sm" wire:click="clearUserFilters" type="button">Limpiar filtros</button>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:.75rem;">{{ $users->links() }}</div>
    </section>

    @if($showCreateUserModal)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:60;">
            <div class="card card-pad" style="width:min(95vw, 620px); max-height:90vh; overflow:auto;">
                <h3 style="margin-top:0;">Nuevo usuario</h3>
                <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div><label>Nombre</label><input class="input" type="text" wire:model.live="createUserName" autocomplete="off"></div>
                    <div><label>Email</label><input class="input" type="email" wire:model.live="createUserEmail" autocomplete="off"></div>
                    <div><label>Número empleado</label><input class="input" type="text" wire:model.live="createUserEmployeeNumber" autocomplete="off"></div>
                    <div>
                        <label>Rol inicial</label>
                        <select class="select" wire:model.live="createUserRole">
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}">{{ $role->name }} ({{ $role->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label>Contraseña</label><input class="input" type="password" wire:model.live="createUserPassword" autocomplete="new-password"></div>
                    <div><label>Confirmar contraseña</label><input class="input" type="password" wire:model.live="createUserPasswordConfirmation" autocomplete="new-password"></div>
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
                <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div><label>Nombre</label><input class="input" type="text" wire:model.live="editUserName"></div>
                    <div><label>Email</label><input class="input" type="email" wire:model.live="editUserEmail"></div>
                    <div><label>Número empleado</label><input class="input" type="text" wire:model.live="editUserEmployeeNumber"></div>
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
                    <div><label>Nueva contraseña</label><input class="input" type="password" wire:model.live="newPassword"></div>
                    <div><label>Confirmar contraseña</label><input class="input" type="password" wire:model.live="newPasswordConfirmation"></div>
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
                    <button class="btn {{ $confirmAction === 'restore' ? '' : 'btn-danger' }}" wire:click="confirmUserAction" type="button">
                        {{ $confirmAction === 'restore' ? 'Sí, restaurar' : 'Sí, dar de baja' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <section class="card card-pad">
        <div class="page-header" style="margin-bottom:.75rem;">
            <h3 class="page-title">Catálogo de roles y permisos</h3>
            <button class="btn" wire:click="newRole" type="button" data-tooltip="Crear nuevo rol">
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><i data-lucide="shield-plus"></i>Nuevo rol</span>
            </button>
        </div>

        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div><label>Nombre</label><input class="input" type="text" wire:model.live="roleName"></div>
            <div><label>Slug</label><input class="input" type="text" wire:model.live="roleSlug" placeholder="ej: supervisor"></div>
            <div style="grid-column:1 / -1;"><label>Descripción</label><input class="input" type="text" wire:model.live="roleDescription"></div>
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
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><i data-lucide="save"></i>Guardar rol</span>
            </button>
        </div>

        <div class="table-wrap" style="margin-top:1rem;">
            <table class="table-clean">
                <thead><tr><th>ID</th><th>Nombre</th><th>Slug</th><th>Permisos</th><th>Activo</th><th>Acciones</th></tr></thead>
                <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td>{{ $role->id }}</td>
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->slug }}</td>
                        <td>
                            @php($labels = collect((array) $role->permissions)->map(fn ($permission) => $permissionLabels[$permission] ?? $permission)->implode(', '))
                            {{ $labels }}
                        </td>
                        <td>{{ $role->is_active ? 'Sí' : 'No' }}</td>
                        <td><button class="btn" wire:click="editRole({{ $role->id }})" type="button" data-tooltip="Editar rol" aria-label="Editar rol"><span style="display:inline-flex; align-items:center;"><i data-lucide="pencil"></i></span></button></td>
                    </tr>
                @empty
                    <tr><td colspan="6">No hay roles.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
