<div>
    <div class="page-header"><h2 class="page-title">Reglas de notificación</h2></div>
    <section class="card card-pad" style="margin-bottom:1rem;">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div><label>Nombre</label><input class="input" wire:model.live="name" type="text"></div>
            <div><label>Evento</label><input class="input" wire:model.live="event_type" type="text" placeholder="ON_LEAD_STATUS_CHANGED"></div>
            <div><label>Campo condición</label><input class="input" wire:model.live="condition_field" type="text"></div>
            <div>
                <label>Operador</label>
                <select class="select" wire:model.live="condition_operator">
                    <option value="equals">equals</option>
                    <option value="not_equals">not_equals</option>
                    <option value="contains">contains</option>
                    <option value="changed_to">changed_to</option>
                    <option value="is_empty">is_empty</option>
                    <option value="is_not_empty">is_not_empty</option>
                </select>
            </div>
            <div><label>Valor condición</label><input class="input" wire:model.live="condition_value" type="text"></div>
            <div>
                <label>Plantilla predefinida</label>
                <select class="select" wire:model.live="message_template_id">
                    <option value="">Ninguna</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:1 / -1;"><label>Plantilla inline</label><textarea class="textarea" rows="3" wire:model.live="message_template"></textarea></div>
        </div>
        <div style="margin-top:.75rem; display:flex; gap:.75rem; align-items:center;">
            <label style="display:inline-flex; gap:.35rem;"><input type="checkbox" wire:model.live="is_active"> Activa</label>
            <button class="btn btn-primary" wire:click="save" type="button">Guardar</button>
        </div>
    </section>
    <section class="card card-pad">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:.75rem;">
            <div><input class="input" type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar regla"></div>
            <div>
                <select class="select" wire:model.live="eventFilter">
                    <option value="all">Todos los eventos</option>
                    @foreach($events as $event)
                        <option value="{{ $event }}">{{ $event }}</option>
                    @endforeach
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
        <div class="table-wrap"><table class="table-clean"><thead><tr><th>ID</th><th>Nombre</th><th>Evento</th><th>Condición</th><th>Activa</th><th></th></tr></thead><tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->id }}</td><td>{{ $row->name }}</td><td>{{ $row->event_type }}</td>
                    <td>{{ $row->condition_field }} {{ $row->condition_operator }} {{ $row->condition_value }}</td>
                    <td>{{ $row->is_active ? 'Sí' : 'No' }}</td>
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
