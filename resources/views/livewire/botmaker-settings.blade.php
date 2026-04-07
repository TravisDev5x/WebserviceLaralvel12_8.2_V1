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
                <input id="bm-api-url" class="input" type="url" wire:model.live="botmakerApiUrl" placeholder="https://go.botmaker.com/api/v1.0">
                <p class="field-hint muted">URL base para autenticarte. Normalmente no cambia. Copia la que muestra el panel de Botmaker.</p>
                @error('botmakerApiUrl') <small class="text-error">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bm-api-token">Token de autenticación (JWT)</label>
                <div style="display:flex; gap:.5rem; align-items:center;">
                    <input id="bm-api-token" class="input" type="password" wire:model.live="botmakerApiToken" placeholder="Pegar token desde Botmaker" autocomplete="off">
                    <button type="button" class="btn" data-toggle-password="bm-api-token">Ver</button>
                    <button type="button" class="btn" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection">Probar conexión</button>
                </div>
                <p class="field-hint muted">Botmaker → Configuración → API Keys. Déjalo en blanco al guardar si solo usas el token definido en “Tokens de API” en Webhooks autorizados.</p>
                @error('botmakerApiToken') <small class="text-error">{{ $message }}</small> @enderror
            </div>
        </div>
    </section>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title">Endpoint de envío de mensajes</h3>
        <div>
            <label for="bm-send-url">URL para enviar mensajes</label>
            <input id="bm-send-url" class="input" type="url" wire:model.live="botmakerSendMessageUrl" placeholder="https://…/chats-actions/send-messages">
            <p class="field-hint muted">Si está vacío, se usa la URL de la API + <code>/chats-actions/send-messages</code>. Solo cámbiala si Botmaker te indicó otra ruta.</p>
            @error('botmakerSendMessageUrl') <small class="text-error">{{ $message }}</small> @enderror
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
                @error('botmakerSalaryCurrency') <small class="text-error">{{ $message }}</small> @enderror
            </div>
        </div>
        <div class="grid gap-3" style="grid-template-columns: 1fr; margin-top:.75rem;">
            <div>
                <label for="bm-alias">Alias de origen (JSON)</label>
                <textarea id="bm-alias" class="textarea" rows="10" wire:model.live="botmakerSourceAliasesJson" style="font-family:Consolas,monospace;"></textarea>
                @error('botmakerSourceAliasesJson') <small class="text-error">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bm-fields">Campos Bitrix destino (JSON)</label>
                <textarea id="bm-fields" class="textarea" rows="10" wire:model.live="botmakerBitrixFieldsJson" style="font-family:Consolas,monospace;"></textarea>
                @error('botmakerBitrixFieldsJson') <small class="text-error">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bm-enums">Catálogos (JSON)</label>
                <textarea id="bm-enums" class="textarea" rows="10" wire:model.live="botmakerEnumMapsJson" style="font-family:Consolas,monospace;"></textarea>
                @error('botmakerEnumMapsJson') <small class="text-error">{{ $message }}</small> @enderror
            </div>
        </div>
    </section>

    <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" wire:click="save">Guardar configuración</button>
        <a class="btn" href="{{ url('/monitor/settings') }}">Cancelar</a>
    </div>
</div>

<style>
    .settings-section-title { margin: 0 0 .65rem; font-size: 1.05rem; }
    .field-hint { margin: .35rem 0 0; font-size: .82rem; line-height: 1.4; }
    .text-error { color: #dc2626; }
    .health-dot { width: .75rem; height: .75rem; border-radius: 999px; display: inline-block; }
    .health-dot--ok { background: #16a34a; }
    .health-dot--bad { background: #dc2626; }
    .health-dot--neutral { background: #94a3b8; }
</style>
