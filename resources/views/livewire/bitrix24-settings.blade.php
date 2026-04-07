<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Conexión Bitrix24</h2>
            <p class="page-subtitle">URL REST para crear leads y valores por defecto del CRM. Los tokens de eventos salientes de Bitrix se configuran en <a href="{{ url('/monitor/settings/tokens') }}">Webhooks autorizados</a>.</p>
        </div>
        <a class="btn" href="{{ url('/monitor/settings') }}">Volver al centro</a>
    </div>

    @if ($successMessage)
        <section class="card card-pad" style="margin-bottom: 1rem; border-left: 4px solid #16a34a;"><p style="margin:0;">{{ $successMessage }}</p></section>
    @endif
    @if ($errorMessage)
        <section class="card card-pad" style="margin-bottom: 1rem; border-left: 4px solid #dc2626;"><p style="margin:0;">{{ $errorMessage }}</p></section>
    @endif

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="bx-sec-title">Webhook entrante (crear / actualizar leads)</h3>
        <label for="bx-url">URL del webhook entrante de Bitrix24</label>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
            <input id="bx-url" class="input" type="url" wire:model.live="bitrix24WebhookUrl" placeholder="https://tu-portal.bitrix24.com/rest/1/xxxxx/" style="flex:1; min-width:240px;">
            <button type="button" class="btn" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection">Probar conexión</button>
        </div>
        <p class="bx-hint muted">Bitrix24: Aplicaciones, Webhooks, Webhook entrante. La prueba usa primero la URL guardada en Webhooks autorizados (entrantes).</p>
        @error('bitrix24WebhookUrl') <small style="color:#dc2626;">{{ $message }}</small> @enderror
    </section>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="bx-sec-title">Configuración del CRM</h3>
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
            <div>
                <label for="bx-source">Fuente del lead (SOURCE_ID)</label>
                <input id="bx-source" class="input" type="text" wire:model.live="defaultSourceId" placeholder="WEB, WHATSAPP o ID numérico">
                <p class="bx-hint muted">Opcional. CRM &gt; Configuración &gt; Fuentes.</p>
                @error('defaultSourceId') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bx-assign">Responsable (ASSIGNED_BY_ID)</label>
                <input id="bx-assign" class="input" type="text" wire:model.live="defaultAssignedById" placeholder="ej. 1">
                <p class="bx-hint muted">ID de usuario en Bitrix24.</p>
                @error('defaultAssignedById') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bx-status">Estatus inicial (STATUS_ID)</label>
                <input id="bx-status" class="input" type="text" wire:model.live="defaultStatusId" placeholder="ej. NEW">
                <p class="bx-hint muted">Estatus al crear el lead.</p>
                @error('defaultStatusId') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
        </div>
    </section>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="bx-sec-title">Estado de la conexión</h3>
        <div style="display:flex; align-items:center; gap:.75rem; flex-wrap:wrap;">
            @if($testMessage === null && !$lastTestAt)
                <span class="bx-dot bx-dot--n"></span><span class="muted">No probado aún</span>
            @elseif($testOk)
                <span class="bx-dot bx-dot--ok"></span><span style="color:#166534;">Última prueba exitosa @if($lastTestAt) ({{ $lastTestAt }}) @endif</span>
            @else
                <span class="bx-dot bx-dot--bad"></span><span style="color:#b91c1c;">{{ $testMessage }}</span>
            @endif
        </div>
        <button type="button" class="btn" style="margin-top:.5rem;" wire:click="testConnection" wire:loading.attr="disabled">Probar ahora</button>
        <span class="muted" wire:loading wire:target="testConnection">Probando…</span>
    </section>

    <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" wire:click="save">Guardar configuración</button>
        <a class="btn" href="{{ url('/monitor/settings') }}">Cancelar</a>
    </div>
</div>

<style>
    .bx-sec-title { margin: 0 0 .65rem; font-size: 1.05rem; }
    .bx-hint { margin: .35rem 0 0; font-size: .82rem; line-height: 1.4; }
    .bx-dot { width: .75rem; height: .75rem; border-radius: 999px; display: inline-block; }
    .bx-dot--ok { background: #16a34a; }
    .bx-dot--bad { background: #dc2626; }
    .bx-dot--n { background: #94a3b8; }
</style>
