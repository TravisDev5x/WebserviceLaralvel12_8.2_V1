<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Mapeo de campos dinámico</h2>
            <p class="page-subtitle">Configura origen y destino sin tocar código.</p>
        </div>
    </div>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div><label>Plataforma origen</label><input class="input" wire:model.live="source_platform" type="text" placeholder="botmaker"><small class="muted">Sistema origen del dato. Ejemplo: <code>botmaker</code>.</small></div>
            <div><label>Campo origen</label><input class="input" wire:model.live="source_field" type="text" placeholder="firstName"><small class="muted">Campo que llega desde Botmaker. Ejemplo: <code>firstName</code>, <code>messages.0.message</code>.</small></div>
            <div><label>Path origen</label><input class="input" wire:model.live="source_path" type="text" placeholder="messages.0.message"><small class="muted">Ruta anidada dentro del payload. Ejemplo: <code>contactId</code> o <code>sessionId</code>.</small></div>
            <div><label>Plataforma destino</label><input class="input" wire:model.live="target_platform" type="text" placeholder="bitrix24"><small class="muted">Sistema destino. Ejemplo: <code>bitrix24</code>.</small></div>
            <div><label>Campo destino</label><input class="input" wire:model.live="target_field" type="text" placeholder="NAME o UF_CRM_1774547362498"><small class="muted">Campo técnico del lead en Bitrix24. Ejemplo: <code>TITLE</code>, <code>PHONE</code>, <code>UF_CRM_1774547362498</code>.</small></div>
            <div><label>Path destino</label><input class="input" wire:model.live="target_path" type="text" placeholder="fields.NAME"><small class="muted">Ruta donde se coloca el valor en el payload final. Ejemplo: <code>fields.COMMENTS</code>.</small></div>
            <div>
                <label>Transformación</label>
                <select class="select" wire:model.live="transform_type">
                    <option value="none">none</option>
                    <option value="uppercase">uppercase</option>
                    <option value="lowercase">lowercase</option>
                    <option value="trim">trim</option>
                    <option value="date_format">date_format</option>
                    <option value="currency">currency</option>
                    <option value="catalog">catalog</option>
                </select>
                <small class="muted">Cómo transformar el valor. Ejemplo: <code>uppercase</code> convierte "juan" en "JUAN".</small>
            </div>
            <div style="grid-column: 1 / -1;"><label>Config transformación (JSON)</label><textarea class="textarea" rows="4" wire:model.live="transform_config" placeholder="{&quot;Activo&quot;:&quot;123&quot;,&quot;Inactivo&quot;:&quot;456&quot;}"></textarea><small class="muted">Configuración de transformación. Para catálogo usa JSON llave=etiqueta y valor=ID Bitrix24. Ejemplo: <code>{"Empleado":"590","Desempleado":"592"}</code>. Para fecha: <code>{"output":"Y-m-d"}</code>.</small></div>
        </div>
        <div style="margin-top: .75rem; display: flex; align-items: center; gap: .75rem;">
            <label style="display: inline-flex; align-items: center; gap: .35rem;"><input type="checkbox" wire:model.live="is_active"> Activo</label>
            <small class="muted">Si está activo, este mapeo se aplica en producción.</small>
            <button class="btn btn-primary" wire:click="save" type="button">Guardar</button>
        </div>
    </section>

    <section class="card card-pad">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: .75rem;">
            <div><input class="input" type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar campo o path"></div>
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
        <div class="table-wrap">
            <table class="table-clean">
                <thead><tr><th>ID</th><th>Origen</th><th>Destino</th><th>Transformación</th><th>Activo</th><th>Acciones</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->source_platform }}.{{ $row->source_field }}</td>
                        <td>{{ $row->target_platform }}.{{ $row->target_field }}</td>
                        <td>{{ $row->transform_type ?: 'none' }}</td>
                        <td>{{ $row->is_active ? 'Sí' : 'No' }}</td>
                        <td style="display:flex; gap:.35rem;">
                            <button class="btn" wire:click="edit({{ $row->id }})" type="button">Editar</button>
                            <button class="btn btn-danger" wire:click="confirmDelete({{ $row->id }})" type="button">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Sin registros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:.75rem;">{{ $rows->links() }}</div>
    </section>

    @if($deleteId)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:50;">
            <div class="card card-pad" style="width:min(92vw, 420px);">
                <h3 style="margin-top:0;">Confirmar eliminación</h3>
                <p>¿Seguro que deseas eliminar este registro?</p>
                <div style="display:flex; justify-content:flex-end; gap:.5rem;">
                    <button class="btn" wire:click="cancelDelete" type="button">Cancelar</button>
                    <button class="btn btn-danger" wire:click="deleteConfirmed" type="button">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
