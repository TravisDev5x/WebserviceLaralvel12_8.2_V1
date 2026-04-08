<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Conexión Botmaker</h2>
            <p class="page-subtitle">Credenciales de la API y mapeo avanzado. Los secretos de webhook entrante se gestionan en <a href="{{ url('/monitor/settings/tokens') }}">Webhooks autorizados</a>.</p>
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
        <h3 class="settings-section-title">Credenciales de API</h3>
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div>
                <label for="bm-api-url">URL de la API de Botmaker</label>
                <input id="bm-api-url" class="input" type="url" wire:model.live="botmakerApiUrl" placeholder="https://api.botmaker.com/v2.0">
                <small class="field-help muted">URL base de la API de Botmaker. Se obtiene en Botmaker &gt; Integraciones &gt; API. Ejemplo: <code>https://api.botmaker.com/v2.0</code>.</small>
                @error('botmakerApiUrl') <small class="text-error">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bm-api-token">Token de autenticación (JWT)</label>
                <div style="display:flex; gap:.5rem; align-items:center;">
                    <input id="bm-api-token" class="input" type="password" wire:model.live="botmakerApiToken" placeholder="eyJhbGciOiJIUzUxMiJ9.eyJzdWIi..." autocomplete="off">
                    <button type="button" class="btn" data-toggle-password="bm-api-token">Ver</button>
                    <button type="button" class="btn" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection">Probar conexión</button>
                </div>
                <small class="field-help muted">Token JWT para autenticarte con la API de Botmaker. Se obtiene en Botmaker &gt; Configuración &gt; API Keys &gt; Access Token. Ejemplo: <code>eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJFZHVhcmRvI...</code>. ⚠️ Si la prueba da 401, el token no es válido para consulta.</small>
                @error('botmakerApiToken') <small class="text-error">{{ $message }}</small> @enderror
            </div>
        </div>
    </section>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title">Estado de la conexión</h3>
        <div style="display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; margin-bottom:.5rem;">
            @if($testMessage === null && !$lastTestAt)
                <span class="health-dot health-dot--neutral" title="Sin prueba"></span>
                <span class="muted">No probado aún</span>
            @elseif($testOk)
                <span class="health-dot health-dot--ok" title="OK"></span>
                <span style="color:#166534;">Última prueba exitosa @if($lastTestAt) ({{ $lastTestAt }}) @endif</span>
            @else
                <span class="health-dot health-dot--bad" title="Error"></span>
                <span style="color:#b91c1c;">{{ $testMessage }}</span>
            @endif
        </div>
        <button type="button" class="btn" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection">Probar ahora</button>
        <span class="muted" wire:loading wire:target="testConnection" style="margin-left:.5rem;">Probando…</span>
        @if($testMessage && $lastTestAt)
            <p class="muted" style="margin:.5rem 0 0; font-size:.85rem;">Registrado: {{ $lastTestAt }}</p>
        @endif
    </section>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title">Mapeo avanzado (JSON)</h3>
        <p class="muted field-hint">Para usuarios avanzados. La moneda se usa al interpretar salarios.</p>
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
            <div>
                <label for="bm-currency">Moneda (ISO 4217)</label>
                <input id="bm-currency" class="input" maxlength="3" wire:model.live="botmakerSalaryCurrency" placeholder="MXN">
                <small class="field-help muted">Moneda ISO para montos convertidos. Se obtiene del catálogo de moneda del negocio. Ejemplo: <code>MXN</code>.</small>
                @error('botmakerSalaryCurrency') <small class="text-error">{{ $message }}</small> @enderror
            </div>
        </div>
        <div class="grid gap-3" style="grid-template-columns: 1fr; margin-top:.75rem;">
            <div>
                <label for="bm-alias">Alias de origen (JSON)</label>
                <textarea id="bm-alias" class="textarea" rows="10" wire:model.live="botmakerSourceAliasesJson" style="font-family:Consolas,monospace;" placeholder="{&quot;nombre&quot;:[&quot;firstName&quot;,&quot;name&quot;]}"></textarea>
                <small class="field-help muted">Alias de campos entrantes para estandarizar nombres. Se obtiene del payload real de Botmaker. Ejemplo: <code>{"nombre":["firstName","name"]}</code>.</small>
                @error('botmakerSourceAliasesJson') <small class="text-error">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bm-fields">Campos Bitrix destino (JSON)</label>
                <textarea id="bm-fields" class="textarea" rows="10" wire:model.live="botmakerBitrixFieldsJson" style="font-family:Consolas,monospace;" placeholder="{&quot;nombre_completo&quot;:&quot;UF_CRM_1774547362498&quot;}"></textarea>
                <small class="field-help muted">Mapa de campos destino de Bitrix24. Se obtiene de CRM &gt; Configuración &gt; Campos del lead. Ejemplo: <code>{"nombre_completo":"UF_CRM_1774547362498"}</code>.</small>
                @error('botmakerBitrixFieldsJson') <small class="text-error">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bm-enums">Catálogos (JSON)</label>
                <textarea id="bm-enums" class="textarea" rows="10" wire:model.live="botmakerEnumMapsJson" style="font-family:Consolas,monospace;" placeholder="{&quot;estatus_laboral&quot;:{&quot;Empleado&quot;:&quot;590&quot;,&quot;Desempleado&quot;:&quot;592&quot;}}"></textarea>
                <small class="field-help muted">Catálogo para convertir etiquetas a IDs de Bitrix24. Se obtiene de los valores de lista del CRM. Ejemplo: <code>{"estatus_laboral":{"Empleado":"590","Desempleado":"592"}}</code>.</small>
                @error('botmakerEnumMapsJson') <small class="text-error">{{ $message }}</small> @enderror
            </div>
        </div>
    </section>

    <div id="unsaved-banner-bm" class="card card-pad" style="display:none; margin-bottom:.75rem; border-left:4px solid #eab308;">
        Tienes cambios sin guardar
    </div>
    <div class="sticky-save-bar" style="display:flex; gap:.5rem; flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" wire:click="save">Guardar configuración</button>
        <a class="btn" href="{{ url('/monitor/settings') }}">Cancelar</a>
    </div>
    @if ($successMessage)
        <div class="toast-ok">Configuración guardada correctamente</div>
    @endif
</div>

<style>
    .settings-section-title { margin: 0 0 .65rem; font-size: 1.05rem; }
    .field-hint { margin: .35rem 0 0; font-size: .82rem; line-height: 1.4; }
    .field-help { display:block; margin:.35rem 0 0; font-size:.8rem; line-height:1.4; color:var(--app-muted); }
    .text-error { color: #dc2626; }
    .health-dot { width: .75rem; height: .75rem; border-radius: 999px; display: inline-block; }
    .health-dot--ok { background: #16a34a; }
    .health-dot--bad { background: #dc2626; }
    .health-dot--neutral { background: #94a3b8; }
    .sticky-save-bar { position: sticky; bottom: .5rem; background: var(--app-surface); padding: .6rem; border: 1px solid var(--app-border); border-radius: .6rem; }
    .toast-ok { position: fixed; right: 1rem; bottom: 1rem; background:#16a34a; color:#fff; padding:.55rem .8rem; border-radius:.5rem; z-index:50; }
</style>
<script>
    (function () {
        const banner = document.getElementById('unsaved-banner-bm');
        document.querySelectorAll('input,textarea,select').forEach((el) => {
            el.addEventListener('input', () => { if (banner) banner.style.display = 'block'; });
        });
    })();
</script>
