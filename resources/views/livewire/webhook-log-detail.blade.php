<div>
    <div style="margin-bottom: 1rem;">
        <a class="btn" href="{{ url('/monitor/logs') }}">Volver al listado</a>
    </div>

    <div class="page-header" style="margin-bottom: 1rem;">
        <div>
            <h2 class="page-title">Detalle del Webhook #{{ $webhookLog->id }}</h2>
            <p class="page-subtitle">Información técnica y trazabilidad completa del evento.</p>
        </div>
    </div>

    @if ($webhookLog->error_message)
        <div class="alert-destructive mb-4" role="alert">
            <h2 class="text-base font-semibold m-0">Se detectó un error</h2>
            <section>
                <p class="m-0 mt-2 text-sm">{{ $webhookLog->error_message }}</p>
                <p class="muted m-0 mt-2 text-sm">Sugerencia: valida token/credenciales y vuelve a intentar desde Webhooks Fallidos.</p>
            </section>
        </div>
    @endif

    <div class="card card-pad" style="margin-bottom: 1rem;">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
            <p><strong>ID de Correlación:</strong> {{ $webhookLog->correlation_id }}</p>
            <p><strong>Dirección:</strong> {{ $webhookLog->direction }}</p>
            <p><strong>Evento:</strong> {{ $webhookLog->source_event }}</p>
            <p><strong>ID Externo:</strong> {{ $webhookLog->external_id ?: '-' }}</p>
            <p><strong>Estado:</strong> {{ $webhookLog->status }}</p>
            <p><strong>Estado HTTP:</strong> {{ $webhookLog->http_status ?: '-' }}</p>
            <p><strong>Procesamiento ms:</strong> {{ $webhookLog->processing_ms ?: '-' }}</p>
            <p><strong>IP de Origen:</strong> {{ $webhookLog->source_ip ?: '-' }}</p>
            <p><strong>Agente de Usuario:</strong> {{ $webhookLog->user_agent ?: '-' }}</p>
            <p><strong>Creado:</strong> {{ $webhookLog->created_at?->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    <div class="card card-pad" style="margin-bottom: 1rem;">
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.75rem;">
            <button class="btn btn-sm tab-btn btn-primary" data-tab="resumen" type="button">Resumen</button>
            <button class="btn btn-sm tab-btn" data-tab="in" type="button">Datos recibidos</button>
            <button class="btn btn-sm tab-btn" data-tab="out" type="button">Datos enviados</button>
            <button class="btn btn-sm tab-btn" data-tab="resultado" type="button">Resultado</button>
            <button class="btn btn-sm tab-btn" data-tab="error" type="button">Error</button>
        </div>
        <div data-panel="resumen">
            <p><strong>Timeline:</strong></p>
            <p class="muted">Recibido ✓ -> Validado ✓ -> Encolado ✓ -> Procesado {{ $webhookLog->status === 'failed' ? '✗' : '✓' }} -> Enviado {{ $webhookLog->status === 'sent' ? '✓' : '✗' }}</p>
        </div>
        <div data-panel="in" style="display:none;">
            <details open><summary>JSON de entrada</summary><pre style="white-space: pre-wrap; background: #0f172a; color: #e2e8f0; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto;"><code>{{ $payloadInJson }}</code></pre></details>
        </div>
        <div data-panel="out" style="display:none;">
            <details open><summary>JSON de salida</summary><pre style="white-space: pre-wrap; background: #0f172a; color: #e2e8f0; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto;"><code>{{ $payloadOutJson }}</code></pre></details>
        </div>
        <div data-panel="resultado" style="display:none;">
            <pre style="white-space: pre-wrap; background: #f8fafc; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto;"><code>{{ $webhookLog->response_body ?: 'Sin cuerpo de respuesta' }}</code></pre>
        </div>
        <div data-panel="error" style="display:none;">
            <pre style="white-space: pre-wrap; background: #fff1f2; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto;"><code>{{ $webhookLog->error_message ?: 'Sin error registrado' }}</code></pre>
        </div>
    </div>

    @if ($webhookLog->failedWebhook)
        <div class="card card-pad">
            <h3 style="margin: 0 0 0.5rem;">FailedWebhook relacionado</h3>
            <p><strong>Intentos:</strong> {{ $webhookLog->failedWebhook->attempts }}</p>
            <p><strong>Próximo Reintento:</strong> {{ $webhookLog->failedWebhook->next_retry_at?->format('Y-m-d H:i:s') ?: '-' }}</p>
            <p><strong>Último Error:</strong> {{ $webhookLog->failedWebhook->last_error ?: '-' }}</p>
            <p><strong>Estado:</strong> {{ $webhookLog->failedWebhook->status }}</p>
        </div>
    @endif
</div>
<script>
    (function () {
        const root = document.currentScript?.previousElementSibling?.parentElement || document;
        const btns = root.querySelectorAll('.tab-btn');
        const panels = root.querySelectorAll('[data-panel]');
        btns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const tab = btn.getAttribute('data-tab');
                btns.forEach((b) => b.classList.remove('btn-primary'));
                btn.classList.add('btn-primary');
                panels.forEach((p) => p.style.display = p.getAttribute('data-panel') === tab ? 'block' : 'none');
            });
        });
    })();
</script>
