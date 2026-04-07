<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Webhooks autorizados</h2>
            <p class="page-subtitle">Tokens y URLs por tipo de flujo. Sin registros activos, el sistema usa la configuración guardada o el archivo .env.</p>
        </div>
        <a class="btn" href="{{ url('/monitor/settings') }}">Volver al centro</a>
    </div>

    @if ($successMessage)
        <section class="card card-pad" style="margin-bottom: 1rem; border-left: 4px solid #16a34a;"><p style="margin:0;">{{ $successMessage }}</p></section>
    @endif
    @if ($errorMessage)
        <section class="card card-pad" style="margin-bottom: 1rem; border-left: 4px solid #dc2626;"><p style="margin:0;">{{ $errorMessage }}</p></section>
    @endif
    @if ($testRowMessage)
        <section class="card card-pad" style="margin-bottom: 1rem;"><p style="margin:0;">Resultado prueba URL: <strong>{{ $testRowMessage }}</strong></p></section>
    @endif

    <div style="display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem;">
        <button type="button" class="btn @if($platformTab==='bitrix24') btn-primary @endif" wire:click="$set('platformTab','bitrix24')">Bitrix24</button>
        <button type="button" class="btn @if($platformTab==='botmaker') btn-primary @endif" wire:click="$set('platformTab','botmaker')">Botmaker</button>
    </div>

    @if($platformTab === 'bitrix24')
        <div style="display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem;">
            <button type="button" class="btn @if($bitrixSub==='incoming') btn-primary @endif" wire:click="$set('bitrixSub','incoming')">Webhooks entrantes (middleware hacia Bitrix24)</button>
            <button type="button" class="btn @if($bitrixSub==='outgoing') btn-primary @endif" wire:click="$set('bitrixSub','outgoing')">Webhooks salientes (Bitrix24 hacia middleware)</button>
        </div>
        @if($bitrixSub === 'incoming')
            <p class="muted" style="margin:0 0 1rem; font-size:.88rem; line-height:1.45;">URLs REST para que el middleware cree o actualice leads. Se crean en Bitrix24: Aplicaciones, Webhooks, Webhook entrante.</p>
        @else
            <p class="muted" style="margin:0 0 1rem; font-size:.88rem; line-height:1.45;">El valor <code>auth.application_token</code> que envía Bitrix24 al notificar cambios en el CRM.</p>
        @endif
    @else
        <div style="display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem;">
            <button type="button" class="btn @if($botmakerSub==='api') btn-primary @endif" wire:click="$set('botmakerSub','api')">Tokens de API (middleware hacia Botmaker)</button>
            <button type="button" class="btn @if($botmakerSub==='webhook') btn-primary @endif" wire:click="$set('botmakerSub','webhook')">Tokens de webhook (Botmaker hacia middleware)</button>
        </div>
        @if($botmakerSub === 'api')
            <p class="muted" style="margin:0 0 1rem; font-size:.88rem;">JWT para llamar a la API de Botmaker. Si hay varios activos, se usa el primero; también puedes guardar el token en la pantalla Conexión Botmaker.</p>
        @else
            <p class="muted" style="margin:0 0 1rem; font-size:.88rem;">Secreto que Botmaker envía en la cabecera <code>X-Botmaker-Signature</code>.</p>
        @endif
    @endif

    <section class="card card-pad" style="margin-bottom:1rem;">
        <h3 style="margin:0 0 .75rem; font-size:1rem;">@if($editingId) Editar @else Nuevo @endif registro</h3>
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
            <div>
                <label>Etiqueta</label>
                <input class="input" type="text" wire:model.live="label" maxlength="100" placeholder="@if($platformTab==='bitrix24' && $bitrixSub==='incoming')Webhook principal CRM@elseif($platformTab==='bitrix24')Eventos de leads CRM@elseif($botmakerSub==='api')API Key principal@else Webhook principal Botmaker @endif">
                <small class="muted" style="display:block;font-size:.8rem;">Nombre para identificar el registro. Ejemplo: "Webhook CRM producción", "Token integración producción".</small>
                @error('label') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            @if($platformTab === 'bitrix24' && $bitrixSub === 'incoming')
                <div style="grid-column:1/-1;">
                    <label>URL del webhook entrante</label>
                    <input class="input" type="url" wire:model.live="webhook_url" placeholder="https://b24-xxxxx.bitrix24.mx/rest/123/token123/">
                    <small class="muted" style="display:block;font-size:.8rem;">URL completa del webhook entrante de Bitrix24. Se obtiene en Aplicaciones &gt; Webhooks. Ejemplo: <code>https://b24-g5r49m.bitrix24.mx/rest/139/yrz3ac4x784xgfr5/</code>.</small>
                    @error('webhook_url') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
            @else
                <div style="grid-column:1/-1;">
                    <label>Token @if($editingId) <span class="muted">(vacío = no cambiar)</span> @endif</label>
                    <div style="display:flex;gap:.5rem;align-items:center;">
                        <input id="token-form-value" class="input" type="password" wire:model.live="token" autocomplete="off" placeholder="@if($platformTab==='bitrix24')l3ltqe04c1lcbybm31lyhbdu8l13kr5s@elseif($botmakerSub==='api')eyJhbGciOiJIUzUxMiJ9...@else 107488F1C373F955B1B7125B3650B3BD46C2F9F4 @endif">
                        <button type="button" class="btn btn-sm" data-toggle-password="token-form-value">Ver</button>
                    </div>
                    <small class="muted" style="display:block;font-size:.8rem;">
                        @if($platformTab==='bitrix24' && $bitrixSub==='outgoing')
                            Token del webhook saliente de Bitrix24 (auth[application_token]). Se obtiene en Bitrix24 &gt; Webhook saliente. Ejemplo: <code>l3ltqe04c1lcbybm31lyhbdu8l13kr5s</code>.
                        @elseif($platformTab==='botmaker' && $botmakerSub==='api')
                            JWT de API de Botmaker. Se obtiene en Botmaker &gt; API Keys &gt; Access Token. Ejemplo: <code>eyJhbGciOiJIUzUxMiJ9...</code>.
                        @else
                            Token de seguridad del webhook de Botmaker enviado en <code>auth-bm-token</code>. Debe coincidir en ambos lados. Ejemplo: <code>107488F1C373F955B1B7125B3650B3BD46C2F9F4</code>. ⚠️ Si no coincide, da 401.
                        @endif
                    </small>
                    @error('token') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
            @endif
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
    </section>

    <section class="card card-pad">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; margin-bottom:.75rem;">
            <h3 style="margin:0; font-size:1rem;">Listado</h3>
        </div>
        <div class="table-wrap">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>Etiqueta</th>
                        @if($platformTab==='bitrix24' && $bitrixSub==='incoming')<th>URL</th>@else<th>Token</th>@endif
                        <th>Activo</th>
                        <th>Último uso</th>
                        <th>Notas</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr wire:key="atm-{{ $row->id }}">
                            <td>{{ $row->label }}</td>
                            <td>
                                @if($platformTab==='bitrix24' && $bitrixSub==='incoming')
                                    <code style="font-size:.75rem;">{{ Str::limit($row->webhook_url, 48) }}</code>
                                @else
                                    <code style="font-size:.75rem;">@if(in_array($row->id, $revealedTokenIds, true)){{ $row->token }}@else••••••••@endif</code>
                                    <button type="button" class="btn btn-sm" wire:click="toggleReveal({{ $row->id }})">{{ in_array($row->id, $revealedTokenIds, true) ? 'Ocultar' : 'Ver' }}</button>
                                @endif
                            </td>
                            <td>{{ $row->is_active ? 'Sí' : 'No' }}</td>
                            <td>@if($row->last_used_at){{ $row->last_used_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}@else—@endif</td>
                            <td style="max-width:10rem; font-size:.82rem;" class="muted">{{ Str::limit($row->notes, 40) }}</td>
                            <td style="display:flex; flex-wrap:wrap; gap:.25rem;">
                                @if($platformTab==='bitrix24' && $bitrixSub==='incoming')
                                    <button type="button" class="btn btn-sm" wire:click="testIncomingBitrix({{ $row->id }})">Probar URL</button>
                                @endif
                                <button type="button" class="btn btn-sm" wire:click="edit({{ $row->id }})">Editar</button>
                                <button type="button" class="btn btn-sm" wire:click="toggleActive({{ $row->id }})">{{ $row->is_active ? 'Desactivar' : 'Activar' }}</button>
                                <button type="button" class="btn btn-sm btn-danger" wire:click="confirmDelete({{ $row->id }})">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Sin registros en esta sección.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($deleteId)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:50;">
            <div class="card card-pad" style="width:min(92vw,420px);">
                <h3 style="margin-top:0;">Confirmar eliminación</h3>
                <p>¿Eliminar este registro? Esta acción no se puede deshacer.</p>
                <div style="display:flex; justify-content:flex-end; gap:.5rem;">
                    <button type="button" class="btn" wire:click="cancelDelete">Cancelar</button>
                    <button type="button" class="btn btn-danger" wire:click="deleteConfirmed">Eliminar</button>
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
