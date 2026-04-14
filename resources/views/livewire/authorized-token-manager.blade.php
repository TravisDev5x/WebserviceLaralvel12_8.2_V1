<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Webhooks autorizados</h2>
            <p class="page-subtitle">Gestión de tokens de webhook entrante para Botmaker.</p>
        </div>
        <a class="btn" href="{{ url('/monitor/settings') }}">Volver al centro</a>
    </div>

    @if ($successMessage)
        <div class="alert mb-4" role="status">
            <h2 class="text-base font-semibold m-0">{{ $successMessage }}</h2>
        </div>
    @endif
    @if ($errorMessage)
        <div class="alert-destructive mb-4" role="alert">
            <h2 class="text-base font-semibold m-0">{{ $errorMessage }}</h2>
        </div>
    @endif
    <p class="muted" style="margin:0 0 1rem; font-size:.88rem;">Este token se valida contra la cabecera <code>X-Botmaker-Signature</code> del endpoint <code>/api/webhook/botmaker</code>.</p>

    <div class="card card-pad" style="margin-bottom:1rem;">
        <h3 style="margin:0 0 .75rem; font-size:1rem;">@if($editingId) Editar @else Nuevo @endif registro</h3>
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
            <div>
                <label>Etiqueta</label>
                <input class="input" type="text" wire:model.live="label" maxlength="100" placeholder="Webhook principal Botmaker">
                <small class="muted" style="display:block;font-size:.8rem;">Nombre de referencia del token. Ejemplo: "Botmaker Producción".</small>
                @error('label') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div style="grid-column:1/-1;">
                <label>Token @if($editingId) <span class="muted">(vacío = no cambiar)</span> @endif</label>
                <div style="display:flex;gap:.5rem;align-items:center;">
                    <input id="token-form-value" class="input" type="password" wire:model.live="token" autocomplete="off" placeholder="107488F1C373F955B1B7125B3650B3BD46C2F9F4">
                    <button type="button" class="btn btn-sm" data-toggle-password="token-form-value">Ver</button>
                </div>
                <small class="muted" style="display:block;font-size:.8rem;">Token de seguridad del webhook de Botmaker enviado en <code>X-Botmaker-Signature</code>.</small>
                @error('token') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div style="grid-column:1/-1;">
                <label>Notas (opcional)</label>
                <textarea class="textarea" wire:model.live="notes" rows="2" placeholder="Creado el 01/04/2026 por Operaciones"></textarea>
                <small class="muted" style="display:block;font-size:.8rem;">Notas internas para referencia. No afecta el funcionamiento. Ejemplo: "Creado en producción por Operaciones".</small>
                @error('notes') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <label style="display:inline-flex; gap:.35rem; align-items:center;"><input type="checkbox" wire:model.live="is_active"> Activo</label>
            <small class="muted" style="display:block;font-size:.8rem;">Si está activo, el sistema lo acepta para procesar webhooks.</small>
        </div>
        <div style="margin-top:.75rem; display:flex; gap:.5rem; flex-wrap:wrap;">
            <button type="button" class="btn btn-primary" wire:click="save">Guardar</button>
            @if($editingId)<button type="button" class="btn" wire:click="cancelEdit">Cancelar</button>@endif
            <button type="button" class="btn" wire:click="startCreate">Limpiar formulario</button>
        </div>
    </div>

    <div class="card card-pad">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; margin-bottom:.75rem;">
            <h3 style="margin:0; font-size:1rem;">Listado</h3>
        </div>
        <div class="table-wrap">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>Etiqueta</th>
                        <th>Token</th>
                        <th>Activo</th>
                        <th>Último uso</th>
                        <th>Notas</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @if($rows->count() > 0)
                    @foreach($rows as $row)
                        <tr wire:key="atm-{{ $row->id }}">
                            <td>{{ $row->label }}</td>
                            <td>
                                <code style="font-size:.75rem;">@if(in_array($row->id, $revealedTokenIds, true)){{ $row->token }}@else••••••••@endif</code>
                                <button type="button" class="btn btn-sm" wire:click="toggleReveal({{ $row->id }})">{{ in_array($row->id, $revealedTokenIds, true) ? 'Ocultar' : 'Ver' }}</button>
                            </td>
                            <td>{{ $row->is_active ? 'Sí' : 'No' }}</td>
                            <td>@if($row->last_used_at){{ $row->last_used_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}@else—@endif</td>
                            <td style="max-width:10rem; font-size:.82rem;" class="muted">{{ Str::limit($row->notes, 40) }}</td>
                            <td style="display:flex; flex-wrap:wrap; gap:.25rem;">
                                <button type="button" class="btn btn-sm" wire:click="edit({{ $row->id }})">Editar</button>
                                <button type="button" class="btn btn-sm" wire:click="toggleActive({{ $row->id }})">{{ $row->is_active ? 'Desactivar' : 'Activar' }}</button>
                                <button type="button" class="btn btn-sm btn-destructive" wire:click="confirmDelete({{ $row->id }})">Eliminar</button>
                            </td>
                        </tr>
                    @endforeach
                    @else
                        <tr><td colspan="6">Sin registros en esta sección.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if($deleteId)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:50;">
            <div class="card card-pad" style="width:min(92vw,420px);">
                <h3 style="margin-top:0;">Confirmar eliminación</h3>
                <p>¿Eliminar este registro? Esta acción no se puede deshacer.</p>
                <div style="display:flex; justify-content:flex-end; gap:.5rem;">
                    <button type="button" class="btn" wire:click="cancelDelete">Cancelar</button>
                    <button type="button" class="btn btn-destructive" wire:click="deleteConfirmed">Eliminar</button>
                </div>
            </div>
        </div>
    @endif
</div>
<script>
    (function () {
        document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-toggle-password');
                const input = document.getElementById(id);
                if (!input) return;
                input.type = input.type === 'password' ? 'text' : 'password';
                this.textContent = input.type === 'password' ? 'Ver' : 'Ocultar';
            });
        });
    })();
</script>
