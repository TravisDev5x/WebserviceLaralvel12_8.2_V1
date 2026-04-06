<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Usuarios y roles</h2>
            <p class="page-subtitle">Administración básica (solo admin).</p>
        </div>
        <button class="btn btn-primary" wire:click="openCreateUser" type="button">Nuevo usuario</button>
    </div>
    @if(session('user_created'))
        <div class="card card-pad" style="margin-bottom:.75rem; border-left:3px solid #22c55e; background:#f0fdf4;">
            <p style="margin:0;">{{ session('user_created') }}</p>
        </div>
    @endif
    <section class="card card-pad">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:.75rem;">
            <div><input class="input" type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar usuario"></div>
            <div>
                <select class="select" wire:model.live="roleFilter">
                    <option value="all">Todos los roles</option>
                    <option value="admin">admin</option>
                    <option value="operator">operator</option>
                    <option value="viewer">viewer</option>
                </select>
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
            <table class="table-clean">
                <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Número empleado</th><th>Rol</th><th>Activo</th><th>Último acceso</th><th>Acciones</th></tr></thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->employee_number }}</td>
                            <td>{{ $user->role }}</td>
                            <td>{{ $user->is_active ? 'Sí' : 'No' }}</td>
                            <td>{{ $user->last_login_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                            <td style="display:flex; gap:.35rem;">
                                <button class="btn" wire:click="toggleActive({{ $user->id }})" type="button">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                                <button class="btn" wire:click="setRole({{ $user->id }}, 'admin')" type="button">Admin</button>
                                <button class="btn" wire:click="setRole({{ $user->id }}, 'operator')" type="button">Operador</button>
                                <button class="btn" wire:click="setRole({{ $user->id }}, 'viewer')" type="button">Viewer</button>
                            </td>
                        </tr>
                    @endforeach
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
                    <div><label>Nombre</label><input class="input" type="text" wire:model.live="newUserName" autocomplete="off"></div>
                    <div><label>Email</label><input class="input" type="email" wire:model.live="newUserEmail" autocomplete="off"></div>
                    <div><label>Número empleado</label><input class="input" type="text" wire:model.live="newUserEmployeeNumber" autocomplete="off"></div>
                    <div>
                        <label>Rol inicial</label>
                        <select class="select" wire:model.live="newUserRole">
                            <option value="admin">admin</option>
                            <option value="operator">operator</option>
                            <option value="viewer">viewer</option>
                        </select>
                    </div>
                    <div><label>Contraseña</label><input class="input" type="password" wire:model.live="newUserPassword" autocomplete="new-password"></div>
                    <div><label>Confirmar contraseña</label><input class="input" type="password" wire:model.live="newUserPasswordConfirmation" autocomplete="new-password"></div>
                </div>
                <div style="margin-top:1rem; display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="closeCreateUser" type="button">Cancelar</button>
                    <button class="btn btn-primary" wire:click="createUser" type="button">Crear usuario</button>
                </div>
            </div>
        </div>
    @endif
</div>
