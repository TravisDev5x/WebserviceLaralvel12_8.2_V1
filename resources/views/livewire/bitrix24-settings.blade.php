<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Conexión Bitrix24</h2>
            <p class="page-subtitle">Configuración OAuth v2 (imconnector / Canal Abierto) y ajustes del conector.</p>
        </div>
        <a class="btn" href="{{ url('/monitor/settings') }}">Volver al centro</a>
    </div>

    @if ($successMessage)
        <section class="card card-pad" style="margin-bottom: 1rem; border-left: 4px solid #16a34a;"><p style="margin:0;">{{ $successMessage }}</p></section>
    @endif
    @if ($errorMessage)
        <section class="card card-pad" style="margin-bottom: 1rem; border-left: 4px solid #dc2626;"><p style="margin:0;">{{ $errorMessage }}</p></section>
    @endif

    {{-- Section 1: OAuth Configuration --}}
    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="bx-sec-title">Credenciales OAuth v2</h3>
        <small class="bx-help muted">Estos valores provienen de la App Local creada en Bitrix24 (Aplicaciones > Developer resources > Otra > App Local). Se guardan en base de datos y sobreescriben los del .env.</small>

        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-top:.75rem;">
            <div>
                <label for="bx-domain">Dominio Bitrix24</label>
                <input id="bx-domain" class="input" type="text" wire:model.live="domain" placeholder="miempresa.bitrix24.mx">
                <small class="bx-help muted">Sin https://. Ejemplo: <code>b24-g5r49m.bitrix24.mx</code></small>
                @error('domain') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bx-client-id">Client ID (App Local)</label>
                <input id="bx-client-id" class="input" type="text" wire:model.live="clientId" placeholder="local.xxxxxxx.xxxxxx">
                @error('clientId') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bx-client-secret">Client Secret</label>
                <input id="bx-client-secret" class="input" type="password" wire:model.live="clientSecret" placeholder="*****">
                @error('clientSecret') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-top:.75rem;">
            <div>
                <label for="bx-connector-id">Connector ID</label>
                <input id="bx-connector-id" class="input" type="text" wire:model.live="connectorId" placeholder="botmaker_whatsapp">
                <small class="bx-help muted">Identificador del conector registrado en Bitrix24.</small>
                @error('connectorId') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bx-line-id">Line ID (Canal Abierto)</label>
                <input id="bx-line-id" class="input" type="text" wire:model.live="lineId" placeholder="1">
                <small class="bx-help muted">ID de la línea de Canal Abierto en Bitrix24.</small>
                @error('lineId') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
        </div>

        <div style="margin-top:1rem; display:flex; gap:.5rem; flex-wrap:wrap;">
            <button type="button" class="btn btn-primary" wire:click="saveOAuth" wire:loading.attr="disabled" wire:target="saveOAuth">Guardar configuración OAuth</button>
            <span class="muted" wire:loading wire:target="saveOAuth">Guardando...</span>
        </div>
    </section>

    {{-- Section 2: OAuth Token Status --}}
    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="bx-sec-title">Estado del Token OAuth</h3>

        @if($this->tokenStatus)
            <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-top:.5rem;">
                <div class="stat-box">
                    <div class="muted">Dominio</div>
                    <strong>{{ $this->tokenStatus['domain'] }}</strong>
                </div>
                <div class="stat-box">
                    <div class="muted">Estado</div>
                    @if($this->tokenStatus['expired'])
                        <span class="badge badge-bad">Expirado</span>
                    @else
                        <span class="badge badge-ok">Válido</span>
                    @endif
                </div>
                <div class="stat-box">
                    <div class="muted">Expira</div>
                    <strong>{{ $this->tokenStatus['expires_at'] ?? 'N/A' }}</strong>
                </div>
                <div class="stat-box">
                    <div class="muted">Último refresh</div>
                    <strong>{{ $this->tokenStatus['updated_at'] ?? 'N/A' }}</strong>
                </div>
            </div>
        @else
            <p class="text-warn">No hay token OAuth almacenado. Instala la App Local en Bitrix24 apuntando a <code>{{ url('/api/bitrix24/install') }}</code>.</p>
        @endif
    </section>

    {{-- Section 3: Connector Setup --}}
    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="bx-sec-title">Configurar Conector en Bitrix24</h3>
        <small class="bx-help muted">Ejecuta el registro, activación y configuración del conector Botmaker WhatsApp en el Canal Abierto. Equivale al comando <code>php artisan bitrix24:setup-connector</code>.</small>

        <div style="margin-top:.75rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;">
            <button type="button" class="btn btn-primary" wire:click="setupConnector" wire:loading.attr="disabled" wire:target="setupConnector">Registrar / Activar conector</button>
            <span class="muted" wire:loading wire:target="setupConnector">Ejecutando setup...</span>
        </div>

        @if($setupMessage)
            <div style="margin-top:.75rem; padding:.6rem; border-radius:.4rem; font-size:.88rem; {{ $setupOk ? 'background:#dcfce7; color:#166534;' : 'background:#fee2e2; color:#b91c1c;' }}">
                {{ $setupMessage }}
            </div>
        @endif
    </section>

    {{-- Section 4: Connection Tests --}}
    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="bx-sec-title">Probar conexión</h3>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
            <button type="button" class="btn" wire:click="testOAuthConnection" wire:loading.attr="disabled" wire:target="testOAuthConnection">Test OAuth Token</button>
            <button type="button" class="btn" wire:click="testConnectorStatus" wire:loading.attr="disabled" wire:target="testConnectorStatus">Test imconnector.status</button>
            <span class="muted" wire:loading wire:target="testOAuthConnection,testConnectorStatus">Probando...</span>
        </div>

        @if($testMessage)
            <div style="margin-top:.75rem; display:flex; align-items:center; gap:.5rem;">
                <span class="bx-dot {{ $testOk ? 'bx-dot--ok' : 'bx-dot--bad' }}"></span>
                <span style="color: {{ $testOk ? '#166534' : '#b91c1c' }};">{{ $testMessage }}</span>
            </div>
        @endif
    </section>

    {{-- Section 5: Legacy v1 (Collapsed) --}}
    <section class="card card-pad" style="margin-bottom: 1rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; cursor:pointer;" wire:click="toggleLegacy">
            <h3 class="bx-sec-title" style="margin:0;">
                <span class="badge badge-legacy">Legacy v1</span>
                Webhook CRM (crm.lead.add)
            </h3>
            <span style="font-size:1.2rem; color:var(--app-muted);">{{ $showLegacy ? '▲' : '▼' }}</span>
        </div>
        <small class="bx-help muted">Configuración del webhook REST para crear leads (v1). En v2, Bitrix24 crea leads automáticamente desde el Canal Abierto.</small>

        @if($showLegacy)
            <div style="margin-top:1rem; padding-top:.75rem; border-top:1px solid var(--app-border);">
                <div style="margin-bottom:.75rem;">
                    <label for="bx-url-legacy">URL del webhook entrante</label>
                    <input id="bx-url-legacy" class="input" type="url" wire:model.live="bitrix24WebhookUrl" placeholder="https://b24-xxxxx.bitrix24.mx/rest/123/abc456xyz/" style="width:100%;">
                    @error('bitrix24WebhookUrl') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>

                <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div>
                        <label for="bx-source-legacy">Fuente del lead (SOURCE_ID)</label>
                        <input id="bx-source-legacy" class="input" type="text" wire:model.live="defaultSourceId" placeholder="WEB, WHATSAPP">
                        @error('defaultSourceId') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                    </div>
                    <div>
                        <label for="bx-assign-legacy">Responsable (ASSIGNED_BY_ID)</label>
                        <input id="bx-assign-legacy" class="input" type="text" wire:model.live="defaultAssignedById" placeholder="139">
                        @error('defaultAssignedById') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                    </div>
                    <div>
                        <label for="bx-status-legacy">Estatus inicial (STATUS_ID)</label>
                        <input id="bx-status-legacy" class="input" type="text" wire:model.live="defaultStatusId" placeholder="NEW">
                        @error('defaultStatusId') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div style="margin-top:.75rem;">
                    <button type="button" class="btn" wire:click="saveLegacy" wire:loading.attr="disabled" wire:target="saveLegacy">Guardar Legacy</button>
                    <span class="muted" wire:loading wire:target="saveLegacy">Guardando...</span>
                </div>
            </div>
        @endif
    </section>
</div>

<style>
    .bx-sec-title { margin: 0 0 .65rem; font-size: 1.05rem; }
    .bx-help { display:block; margin:.35rem 0 0; font-size:.8rem; line-height:1.4; color:var(--app-muted); }
    .bx-dot { width: .75rem; height: .75rem; border-radius: 999px; display: inline-block; }
    .bx-dot--ok { background: #16a34a; }
    .bx-dot--bad { background: #dc2626; }
    .badge { padding: .2rem .45rem; border-radius: .45rem; font-size: .78rem; }
    .badge-ok { background: #dcfce7; color: #166534; }
    .badge-bad { background: #fee2e2; color: #b91c1c; }
    .badge-legacy { background: #fef3c7; color: #92400e; margin-right: .4rem; }
    .stat-box { border: 1px solid var(--app-border); border-radius: .6rem; padding: .65rem; }
    .text-warn { color: #92400e; margin-top: .75rem; }
</style>
