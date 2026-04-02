<div>
    <div class="page-header"><h2 class="page-title">Filtros de eventos</h2></div>
    <section class="card card-pad" style="margin-bottom:1rem;">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div>
                <label>Plataforma</label>
                <select class="select" wire:model.live="platform">
                    <option value="botmaker">botmaker</option>
                    <option value="bitrix24">bitrix24</option>
                </select>
            </div>
            <div><label>Evento</label><input class="input" wire:model.live="event_type" type="text"></div>
            <div><label>Campo</label><input class="input" wire:model.live="filter_field" type="text"></div>
            <div>
                <label>Operador</label>
                <select class="select" wire:model.live="filter_operator">
                    <option value="equals">equals</option>
                    <option value="not_equals">not_equals</option>
                    <option value="contains">contains</option>
                    <option value="not_contains">not_contains</option>
                    <option value="is_empty">is_empty</option>
                    <option value="is_not_empty">is_not_empty</option>
                </select>
            </div>
            <div><label>Valor</label><input class="input" wire:model.live="filter_value" type="text"></div>
            <div>
                <label>Acción</label>
                <select class="select" wire:model.live="action">
                    <option value="process">process</option>
                    <option value="ignore">ignore</option>
                </select>
            </div>
            <div style="grid-column:1 / -1;"><label>Descripción</label><input class="input" wire:model.live="description" type="text"></div>
        </div>
        <div style="margin-top:.75rem; display:flex; gap:.75rem;">
            <label style="display:inline-flex; gap:.35rem;"><input type="checkbox" wire:model.live="is_active"> Activo</label>
            <button class="btn btn-primary" wire:click="save" type="button">Guardar</button>
        </div>
    </section>
    <section class="card card-pad">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:.75rem;">
            <div><input class="input" type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar filtro"></div>
            <div>
                <select class="select" wire:model.live="platformFilter">
                    <option value="all">Todas las plataformas</option>
                    <option value="botmaker">Botmaker</option>
                    <option value="bitrix24">Bitrix24</option>
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
        <div class="table-wrap"><table class="table-clean"><thead><tr><th>ID</th><th>Plataforma</th><th>Evento</th><th>Filtro</th><th>Acción</th><th></th></tr></thead><tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->id }}</td><td>{{ $row->platform }}</td><td>{{ $row->event_type }}</td>
                    <td>{{ $row->filter_field }} {{ $row->filter_operator }} {{ $row->filter_value }}</td>
                    <td>{{ $row->action }}</td>
                    <td style="display:flex; gap:.35rem;"><button class="btn" wire:click="edit({{ $row->id }})" type="button">Editar</button><button class="btn btn-danger" wire:click="confirmDelete({{ $row->id }})" type="button">Eliminar</button></td>
                </tr>
            @empty
                <tr><td colspan="6">Sin filtros.</td></tr>
            @endforelse
        </tbody></table></div>
        <div style="margin-top:.75rem;">{{ $rows->links() }}</div>
    </section>
    @if($deleteId)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:50;">
            <div class="card card-pad" style="width:min(92vw, 420px);">
                <h3 style="margin-top:0;">Confirmar eliminación</h3>
                <p>¿Seguro que deseas eliminar este filtro?</p>
                <div style="display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="cancelDelete" type="button">Cancelar</button>
                    <button class="btn btn-danger" wire:click="deleteConfirmed" type="button">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
