<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Conexión Botmaker</h2>
            <p class="page-subtitle">Configuración de recepción de webhooks y envío de mensajes vía API Botmaker.</p>
        </div>
        <a class="btn" href="{{ url('/monitor/settings') }}">Volver al centro</a>
    </div>

    {{-- Section 1: API Config for sending messages (Flujo B) --}}
    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title">Token de API Botmaker (envío de mensajes)</h3>
        <small class="field-help muted">Credenciales para enviar mensajes a WhatsApp vía Botmaker (Flujo B: agente → cliente). Se obtienen en el panel de Botmaker > Configuración > API/Integraciones.</small>

        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-top:.75rem;">
            <div>
                <label for="bm-api-url">URL base de API</label>
                <input id="bm-api-url" class="input" type="url" wire:model.live="apiUrl" placeholder="https://go.botmaker.com/api/v1.0">
                <small class="field-help muted">URL base de la API REST de Botmaker. Default: <code>https://go.botmaker.com/api/v1.0</code></small>
                @error('apiUrl') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bm-api-token">Token de API (access-token)</label>
                <div style="display:flex; gap:.35rem;">
                    <input id="bm-api-token" class="input" type="{{ $apiTokenVisible ? 'text' : 'password' }}" wire:model.live="apiToken" placeholder="eyJhbGciOiJSUzI1NiIs..." style="flex:1;">
                    <button type="button" class="btn btn-sm" wire:click="toggleApiTokenVisibility">{{ $apiTokenVisible ? 'Ocultar' : 'Ver' }}</button>
                </div>
                <small class="field-help muted">JWT token de Botmaker. Se envía como header <code>access-token</code> en cada petición a su API.</small>
                @error('apiToken') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="bm-send-endpoint">Endpoint de envío</label>
                <input id="bm-send-endpoint" class="input" type="text" wire:model.live="sendEndpoint" placeholder="/message/v2">
                <small class="field-help muted">Ruta del endpoint para enviar mensajes. Default: <code>/message/v2</code></small>
                @error('sendEndpoint') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
        </div>

        <div style="margin-top:.75rem; display:flex; align-items:center; gap:.75rem; flex-wrap:wrap;">
            <button type="button" class="btn btn-primary" wire:click="saveApiConfig" wire:loading.attr="disabled" wire:target="saveApiConfig">Guardar configuración API</button>
            <button type="button" class="btn" wire:click="testApiToken" wire:loading.attr="disabled" wire:target="testApiToken">Probar conexión API</button>
            <span class="muted" wire:loading wire:target="saveApiConfig,testApiToken">Procesando...</span>
        </div>

        @if($apiSaveMessage)
            <p style="margin-top:.5rem; font-size:.9rem; color: {{ $apiSaveOk ? '#166534' : '#b91c1c' }};">{{ $apiSaveMessage }}</p>
        @endif

        @if($apiTestMessage)
            <div style="margin-top:.5rem; display:flex; align-items:center; gap:.5rem;">
                <span class="bx-dot {{ $apiTestOk ? 'bx-dot--ok' : 'bx-dot--bad' }}"></span>
                <span style="font-size:.9rem; color: {{ $apiTestOk ? '#166534' : '#b91c1c' }};">{{ $apiTestMessage }}</span>
            </div>
        @endif

        <div style="margin-top:.75rem; padding:.5rem; background:var(--sidebar-row); border-radius:.4rem; font-size:.82rem;">
            <strong>Fuente actual del token:</strong> <code>{{ $this->resolvedTokenSource }}</code>
            <br><small class="muted">Prioridad de resolución: Panel (settings) → AuthorizedTokens (DB) → .env</small>
        </div>
    </section>

    {{-- Section 2: Webhook URL --}}
    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title" title="Copia esta URL en el panel de Botmaker">URL del webhook (recepción de mensajes)</h3>
        <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
            <input id="botmaker-webhook-url" class="input" type="text" value="{{ $webhookUrl }}" readonly style="min-width: 320px;">
            <button type="button" class="btn" onclick="copyWebhookUrl()">Copiar</button>
        </div>
        <small class="field-help muted">Esta es la URL que Telecomunicaciones debe configurar como webhook de salida en el panel de Botmaker. Debe ser HTTPS.</small>
        @if($appUrlIsHttp)
            <p class="text-warn">Botmaker requiere HTTPS. Contacta a Infraestructura para configurar el certificado SSL.</p>
        @endif
    </section>

    {{-- Section 3: Webhook validation tokens --}}
    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title" title="Tokens para validar webhooks entrantes">Token de seguridad (validación de webhooks)</h3>
        <small class="field-help muted">El token de seguridad se envía en el header <code>auth-bm-token</code>. Debe coincidir exactamente con el configurado en Botmaker.</small>

        <div style="margin-top:.75rem; overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Etiqueta</th>
                        <th>Valor</th>
                        <th>Estado</th>
                        <th>Último uso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @if($this->botmakerTokens->isEmpty())
                        <tr>
                            <td colspan="5" class="muted">No hay tokens de Botmaker registrados.</td>
                        </tr>
                    @endif
                    @foreach($this->botmakerTokens as $token)
                        <tr>
                            <td>{{ $token->label !== '' ? $token->label : 'Sin etiqueta' }}</td>
                            <td>
                                @if(!empty($visibleTokens[$token->id]))
                                    <code>{{ $token->token }}</code>
                                @else
                                    <code>{{ str_repeat('*', 12) }}</code>
                                @endif
                            </td>
                            <td>
                                @if($token->is_active)
                                    <span class="badge badge-ok">Activo</span>
                                @else
                                    <span class="badge badge-muted">Inactivo</span>
                                @endif
                            </td>
                            <td>{{ $token->last_used_at ? $token->last_used_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s') : 'Nunca' }}</td>
                            <td>
                                <button type="button" class="btn btn-sm" wire:click="toggleTokenVisibility({{ $token->id }})">
                                    @if(!empty($visibleTokens[$token->id])) Ocultar @else Ver @endif
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:.75rem;">
            <a class="btn" href="{{ url('/monitor/settings/tokens') }}">Gestionar tokens</a>
        </div>
    </section>

    {{-- Section 4: Stats --}}
    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title" title="Resumen de actividad reciente">Estado de la recepción</h3>
        <small class="field-help muted">Monitorea la llegada de webhooks Botmaker hacia este webservice.</small>

        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-top:.75rem;">
            <div class="stat-box">
                <div class="muted">Último webhook recibido</div>
                <strong>{{ $lastWebhookAt ?? 'Sin registros' }}</strong>
            </div>
            <div class="stat-box">
                <div class="muted">Total webhooks recibidos hoy</div>
                <strong>{{ $totalToday }}</strong>
            </div>
            <div class="stat-box">
                <div class="muted">Webhooks exitosos hoy</div>
                <strong>{{ $successToday }}</strong>
            </div>
            <div class="stat-box">
                <div class="muted">Webhooks fallidos hoy</div>
                <strong>{{ $failedToday }}</strong>
            </div>
        </div>

        @if($lastWebhookAt === null)
            <p class="text-warn">No se han recibido webhooks todavía. Configura la URL en Botmaker para comenzar.</p>
        @elseif(\Carbon\Carbon::parse($lastWebhookAt)->lt(now()->subHours(24)))
            <p class="text-warn">No se han recibido webhooks en las últimas 24 horas. Verifica que el webhook en Botmaker esté activo.</p>
        @endif
    </section>

    {{-- Section 5: Test webhook --}}
    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title" title="Prueba controlada del endpoint local">Probar recepción de webhook</h3>
        <small class="field-help muted">Esto simula un mensaje de Botmaker para verificar que el endpoint funciona.</small>
        <div style="display:flex; align-items:center; gap:.5rem; margin-top:.75rem;">
            <button type="button" class="btn btn-primary" wire:click="sendTestWebhook" wire:loading.attr="disabled" wire:target="sendTestWebhook">Enviar webhook de prueba</button>
            <span class="muted" wire:loading wire:target="sendTestWebhook">Enviando...</span>
        </div>
        @if($testMessage)
            <p style="margin-top:.75rem;" @class(['text-ok' => $testOk, 'text-error' => ! $testOk])>{{ $testMessage }}</p>
        @endif
    </section>

    <style>
        .settings-section-title { margin: 0 0 .65rem; font-size: 1.05rem; }
        .field-help { display:block; margin:.35rem 0 0; font-size:.82rem; line-height:1.4; color:var(--app-muted); }
        .text-error { color: #b91c1c; }
        .text-ok { color: #166534; }
        .text-warn { color: #92400e; margin-top: .75rem; }
        .badge { padding: .2rem .45rem; border-radius: .45rem; font-size: .78rem; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-muted { background: #e2e8f0; color: #334155; }
        .stat-box { border: 1px solid var(--app-border); border-radius: .6rem; padding: .65rem; }
        .bx-dot { width: .75rem; height: .75rem; border-radius: 999px; display: inline-block; }
        .bx-dot--ok { background: #16a34a; }
        .bx-dot--bad { background: #dc2626; }
    </style>
</div>

<script>
    function copyWebhookUrl() {
        const input = document.getElementById('botmaker-webhook-url');
        if (!input) return;
        const text = input.value;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
            return;
        }
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
    }
</script>
