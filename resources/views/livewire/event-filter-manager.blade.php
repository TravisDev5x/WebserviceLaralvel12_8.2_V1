<div>
    <div class="page-header"><h2 class="page-title">Filtros de eventos</h2></div>
    <section class="card card-pad" style="margin-bottom:1rem;">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div>
                <label>Plataforma</label>
                <select class="select" wire:model.live="platform">
                    <option value="botmaker">Botmaker (WhatsApp)</option>
                    <option value="bitrix24">Bitrix24 (CRM)</option>
                </select>
                <small class="muted">Sistema origen del evento. Botmaker=WhatsApp, Bitrix24=CRM.</small>
            </div>
            <div><label>Tipo de evento</label><input class="input" wire:model.live="event_type" type="text" placeholder="ONCRMLEADUPDATE"><small class="muted">Nombre técnico del evento. Ejemplo: <code>ONCRMLEADUPDATE</code>, <code>ONCRMLEADADD</code>, <code>message</code>.</small></div>
            <div><label>Campo a filtrar</label><input class="input" wire:model.live="filter_field" type="text" placeholder="data.FIELDS.STATUS_ID"><small class="muted">Campo del payload a evaluar. Usa punto para anidados. Ejemplo: <code>test</code>.</small></div>
            <div>
                <label>Operador</label>
                <select class="select" wire:model.live="filter_operator">
                    <option value="equals">Igual a</option>
                    <option value="not_equals">Diferente de</option>
                    <option value="contains">Contiene</option>
                    <option value="not_contains">No contiene</option>
                    <option value="is_empty">Está vacío</option>
                    <option value="is_not_empty">No está vacío</option>
                </select>
                <small class="muted">Tipo de comparación. Ejemplo: <code>equals</code> para "igual a".</small>
            </div>
            <div><label>Valor</label><input class="input" wire:model.live="filter_value" type="text" placeholder="true"><small class="muted">Valor comparado contra el campo. Ejemplo: <code>true</code> o <code>JUNK</code>.</small></div>
            <div>
                <label>Acción</label>
                <select class="select" wire:model.live="action">
                    <option value="process">Procesar normalmente</option>
                    <option value="ignore">Ignorar (descartar)</option>
                </select>
                <small class="muted">Qué hacer si coincide: process=procesar, ignore=descartar como filtrado.</small>
            </div>
            <div style="grid-column:1 / -1;"><label>Descripción</label><input class="input" wire:model.live="description" type="text" placeholder="Ignorar mensajes de prueba de Botmaker"><small class="muted">Descripción interna del filtro y su propósito.</small></div>
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
            @if($rows->count() > 0)
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->id }}</td><td>{{ $row->platform }}</td><td>{{ $row->event_type }}</td>
                    <td>{{ $row->filter_field }} {{ $row->filter_operator }} {{ $row->filter_value }}</td>
                    <td>{{ $row->action }}</td>
                    <td style="display:flex; gap:.35rem;"><button class="btn" wire:click="edit({{ $row->id }})" type="button">Editar</button><button class="btn btn-danger" wire:click="confirmDelete({{ $row->id }})" type="button">Eliminar</button></td>
                </tr>
            @endforeach
            @else
                <tr><td colspan="6">Sin filtros.</td></tr>
            @endif
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
