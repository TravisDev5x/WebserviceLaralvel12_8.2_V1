<div>
    <div class="page-header"><h2 class="page-title">Plantillas de mensajes</h2></div>
    <section class="card card-pad" style="margin-bottom:1rem;">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div><label>Nombre</label><input class="input" wire:model.live="name" type="text"></div>
            <div>
                <label>Categoría</label>
                <select class="select" wire:model.live="category">
                    <option value="notification">notification</option>
                    <option value="confirmation">confirmation</option>
                    <option value="follow_up">follow_up</option>
                    <option value="custom">custom</option>
                </select>
            </div>
            <div style="grid-column:1 / -1;"><label>Body</label><textarea class="textarea" rows="4" wire:model.live="body"></textarea></div>
            <div style="grid-column:1 / -1;"><label>Variables disponibles (coma)</label><input class="input" wire:model.live="variables_available" type="text"></div>
        </div>
        <div style="margin-top:.75rem; display:flex; gap:.75rem; align-items:center;">
            <label style="display:inline-flex; gap:.35rem;"><input type="checkbox" wire:model.live="is_active"> Activa</label>
            <button class="btn btn-primary" wire:click="save" type="button">Guardar</button>
        </div>
    </section>
    <section class="card card-pad">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:.75rem;">
            <div><input class="input" type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar plantilla"></div>
            <div>
                <select class="select" wire:model.live="categoryFilter">
                    <option value="all">Todas las categorías</option>
                    <option value="notification">notification</option>
                    <option value="confirmation">confirmation</option>
                    <option value="follow_up">follow_up</option>
                    <option value="custom">custom</option>
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
        <div class="table-wrap"><table class="table-clean"><thead><tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Activa</th><th></th></tr></thead><tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->id }}</td><td>{{ $row->name }}</td><td>{{ $row->category }}</td><td>{{ $row->is_active ? 'Sí' : 'No' }}</td>
                    <td style="display:flex; gap:.35rem;"><button class="btn" wire:click="edit({{ $row->id }})" type="button">Editar</button><button class="btn btn-danger" wire:click="confirmDelete({{ $row->id }})" type="button">Eliminar</button></td>
                </tr>
            @empty
                <tr><td colspan="5">Sin plantillas.</td></tr>
            @endforelse
        </tbody></table></div>
        <div style="margin-top:.75rem;">{{ $rows->links() }}</div>
    </section>
    @if($deleteId)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:50;">
            <div class="card card-pad" style="width:min(92vw, 420px);">
                <h3 style="margin-top:0;">Confirmar eliminación</h3>
                <p>¿Seguro que deseas eliminar esta plantilla?</p>
                <div style="display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="cancelDelete" type="button">Cancelar</button>
                    <button class="btn btn-danger" wire:click="deleteConfirmed" type="button">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
