<div>
    <div class="page-header"><h2 class="page-title">Números de WhatsApp</h2></div>
    <section class="card card-pad" style="margin-bottom:1rem;">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div><label>Número</label><input class="input" wire:model.live="phone_number" type="text"></div>
            <div><label>Etiqueta</label><input class="input" wire:model.live="label" type="text"></div>
            <div><label>Platform ID</label><input class="input" wire:model.live="platform_id" type="text"></div>
        </div>
        <div style="margin-top:.75rem; display:flex; gap:.75rem;">
            <label style="display:inline-flex; gap:.35rem;"><input type="checkbox" wire:model.live="is_active"> Activo</label>
            <label style="display:inline-flex; gap:.35rem;"><input type="checkbox" wire:model.live="is_default"> Predeterminado</label>
            <button class="btn btn-primary" wire:click="save" type="button">Guardar</button>
        </div>
    </section>
    <section class="card card-pad">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:.75rem;">
            <div><input class="input" type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar número"></div>
            <div>
                <select class="select" wire:model.live="statusFilter">
                    <option value="all">Todos</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>
            </div>
        </div>
        <div class="table-wrap"><table class="table-clean"><thead><tr><th>ID</th><th>Número</th><th>Etiqueta</th><th>Default</th><th></th></tr></thead><tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->id }}</td><td>{{ $row->phone_number }}</td><td>{{ $row->label }}</td><td>{{ $row->is_default ? 'Sí' : 'No' }}</td>
                    <td style="display:flex; gap:.35rem;"><button class="btn" wire:click="edit({{ $row->id }})" type="button">Editar</button><button class="btn btn-danger" wire:click="confirmDelete({{ $row->id }})" type="button">Eliminar</button></td>
                </tr>
            @empty
                <tr><td colspan="5">Sin números.</td></tr>
            @endforelse
        </tbody></table></div>
        <div style="margin-top:.75rem;">{{ $rows->links() }}</div>
    </section>
    @if($deleteId)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:50;">
            <div class="card card-pad" style="width:min(92vw, 420px);">
                <h3 style="margin-top:0;">Confirmar eliminación</h3>
                <p>¿Seguro que deseas eliminar este número?</p>
                <div style="display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="cancelDelete" type="button">Cancelar</button>
                    <button class="btn btn-danger" wire:click="deleteConfirmed" type="button">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
