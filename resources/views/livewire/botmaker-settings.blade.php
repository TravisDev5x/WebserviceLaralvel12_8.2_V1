<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Recepción de webhooks de Botmaker</h2>
            <p class="page-subtitle">Configura cómo el sistema recibe y valida los datos que Botmaker envía cuando un cliente escribe por WhatsApp.</p>
        </div>
        <a class="btn" href="{{ url('/monitor/settings') }}">Volver al centro</a>
    </div>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title" title="Copia esta URL en el panel de Botmaker">URL del webhook (para configurar en Botmaker)</h3>
        <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
            <input id="botmaker-webhook-url" class="input" type="text" value="{{ $webhookUrl }}" readonly style="min-width: 320px;">
            <button type="button" class="btn" onclick="copyWebhookUrl()">Copiar</button>
        </div>
        <small class="field-help muted">Esta es la URL que Telecomunicaciones debe configurar como webhook de salida en el panel de Botmaker. Debe ser HTTPS.</small>
        @if($appUrlIsHttp)
            <p class="text-warn">⚠️ Botmaker requiere HTTPS. Contacta a Infraestructura para configurar el certificado SSL.</p>
        @endif
    </section>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title" title="Tokens para validar webhooks entrantes">Token de seguridad</h3>
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
                            <td>{{ optional($token->last_used_at)->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? 'Nunca' }}</td>
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
            <p class="text-warn">⚠️ No se han recibido webhooks en las últimas 24 horas. Verifica que el webhook en Botmaker esté activo.</p>
        @endif
    </section>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title" title="Pasos mínimos para activar el webhook">Guía rápida</h3>
        <ol style="margin:.4rem 0 0 1.1rem; line-height:1.7;">
            <li>Copia la URL de arriba</li>
            <li>En el panel de Botmaker, ve a Webhooks de salida</li>
            <li>Pega la URL como destino del webhook</li>
            <li>Configura el token de seguridad (debe coincidir con el de la sección 2)</li>
            <li>Guarda en Botmaker</li>
            <li>Envía un mensaje de prueba al WhatsApp del bot</li>
            <li>Regresa aquí y verifica que aparezca en "Estado de la recepción"</li>
        </ol>
    </section>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 class="settings-section-title" title="Prueba controlada del endpoint local">Probar recepción</h3>
        <small class="field-help muted">Esto simula un mensaje de Botmaker para verificar que el endpoint funciona. No crea un lead real en Bitrix24 (se marca como prueba).</small>
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
