<div>
    <div class="page-header"><h2 class="page-title">Reglas de alerta por correo</h2></div>
    <section class="card card-pad" style="margin-bottom:1rem;">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div><label>Nombre</label><input class="input" wire:model.live="name" type="text"></div>
            <div>
                <label>Tipo</label>
                <select class="select" wire:model.live="condition_type">
                    <option value="failed_webhooks">failed_webhooks</option>
                    <option value="webhook_errors">webhook_errors</option>
                    <option value="queue_stuck">queue_stuck</option>
                </select>
            </div>
            <div><label>Umbral</label><input class="input" wire:model.live="threshold" type="number" min="1"></div>
            <div><label>Ventana (min)</label><input class="input" wire:model.live="time_window_minutes" type="number" min="1"></div>
            <div><label>Email</label><input class="input" wire:model.live="notify_email" type="email"></div>
            <div><label>Cooldown (min)</label><input class="input" wire:model.live="cooldown_minutes" type="number" min="1"></div>
        </div>
        <div style="margin-top:.75rem; display:flex; gap:.75rem;">
            <label style="display:inline-flex; gap:.35rem;"><input type="checkbox" wire:model.live="is_active"> Activa</label>
            <button class="btn btn-primary" wire:click="save" type="button">Guardar</button>
        </div>
    </section>
    <section class="card card-pad">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:.75rem;">
            <div><input class="input" type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar alerta"></div>
            <div>
                <select class="select" wire:model.live="typeFilter">
                    <option value="all">Todos los tipos</option>
                    <option value="failed_webhooks">failed_webhooks</option>
                    <option value="webhook_errors">webhook_errors</option>
                    <option value="queue_stuck">queue_stuck</option>
                </select>
            </div>
            <div>
                <select class="select" wire:model.live="statusFilter">
                    <option value="all">Todos</option>
                    <option value="active">Activas</option>
                    <option value="inactive">Inactivas</option>
                </select>
            </div>
        </div>
        <div class="table-wrap"><table class="table-clean"><thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Umbral</th><th>Email</th><th></th></tr></thead><tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->id }}</td><td>{{ $row->name }}</td><td>{{ $row->condition_type }}</td><td>{{ $row->threshold }}</td><td>{{ $row->notify_email }}</td>
                    <td style="display:flex; gap:.35rem;"><button class="btn" wire:click="edit({{ $row->id }})" type="button">Editar</button><button class="btn btn-danger" wire:click="confirmDelete({{ $row->id }})" type="button">Eliminar</button></td>
                </tr>
            @empty
                <tr><td colspan="6">Sin reglas.</td></tr>
            @endforelse
        </tbody></table></div>
        <div style="margin-top:.75rem;">{{ $rows->links() }}</div>
    </section>
    @if($deleteId)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:50;">
            <div class="card card-pad" style="width:min(92vw, 420px);">
                <h3 style="margin-top:0;">Confirmar eliminación</h3>
                <p>¿Seguro que deseas eliminar esta regla?</p>
                <div style="display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="cancelDelete" type="button">Cancelar</button>
                    <button class="btn btn-danger" wire:click="deleteConfirmed" type="button">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
