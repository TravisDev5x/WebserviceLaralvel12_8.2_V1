<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Usuarios y roles</h2>
            <p class="page-subtitle">Administración básica (solo admin).</p>
        </div>
    </div>
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
</div>
