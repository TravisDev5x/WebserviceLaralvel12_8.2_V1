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

    <section class="card card-pad" style="margin-bottom: 1rem;">
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
    </section>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 style="margin: 0 0 0.5rem;">Payload IN</h3>
        <pre style="white-space: pre-wrap; background: #0f172a; color: #e2e8f0; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto;"><code>{{ $payloadInJson }}</code></pre>
    </section>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 style="margin: 0 0 0.5rem;">Payload OUT</h3>
        <pre style="white-space: pre-wrap; background: #0f172a; color: #e2e8f0; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto;"><code>{{ $payloadOutJson }}</code></pre>
    </section>

    @if ($webhookLog->response_body)
        <section class="card card-pad" style="margin-bottom: 1rem;">
            <h3 style="margin: 0 0 0.5rem;">Cuerpo de Respuesta</h3>
            <pre style="white-space: pre-wrap; background: #f8fafc; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto;"><code>{{ $webhookLog->response_body }}</code></pre>
        </section>
    @endif

    @if ($webhookLog->error_message)
        <section class="card card-pad" style="margin-bottom: 1rem; border-left: 4px solid #dc2626;">
            <h3 style="margin: 0 0 0.5rem;">Mensaje de Error</h3>
            <pre style="white-space: pre-wrap; background: #fff1f2; padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto;"><code>{{ $webhookLog->error_message }}</code></pre>
        </section>
    @endif

    @if ($webhookLog->failedWebhook)
        <section class="card card-pad">
            <h3 style="margin: 0 0 0.5rem;">FailedWebhook relacionado</h3>
            <p><strong>Intentos:</strong> {{ $webhookLog->failedWebhook->attempts }}</p>
            <p><strong>Próximo Reintento:</strong> {{ $webhookLog->failedWebhook->next_retry_at?->format('Y-m-d H:i:s') ?: '-' }}</p>
            <p><strong>Último Error:</strong> {{ $webhookLog->failedWebhook->last_error ?: '-' }}</p>
            <p><strong>Estado:</strong> {{ $webhookLog->failedWebhook->status }}</p>
        </section>
    @endif
</div>
