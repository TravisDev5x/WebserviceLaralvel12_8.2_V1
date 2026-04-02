<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Pruebas de integración</h2>
            <p class="page-subtitle">Ejecuta comprobaciones y un lead de prueba en Bitrix24 desde el navegador o vía HTTP (JSON).</p>
        </div>
        <button type="button" class="btn" wire:click="refreshSummary" wire:loading.attr="disabled">Actualizar resumen</button>
    </div>

    @if ($summary)
        <section class="card card-pad" style="margin-bottom: 1rem;">
            <h3 style="margin: 0 0 0.6rem; font-size: 1rem;">Webhooks hoy</h3>
            <div class="grid-auto">
                <div><span class="muted">Total</span><br><strong>{{ $summary['today']['total'] }}</strong></div>
                <div><span class="muted">Enviados</span><br><strong>{{ $summary['today']['sent'] }}</strong></div>
                <div><span class="muted">Fallidos</span><br><strong>{{ $summary['today']['failed'] }}</strong></div>
                <div><span class="muted">En cola / procesando</span><br><strong>{{ $summary['today']['in_queue'] }}</strong></div>
                <div><span class="muted">Fallidos pendientes</span><br><strong>{{ $summary['failed_pending'] }}</strong></div>
            </div>
        </section>
    @endif

    <section class="card card-pad" style="margin-bottom: 1rem; border-left: 4px solid #ca8a04;">
        <p style="margin: 0 0 0.75rem;"><strong>Importante:</strong> «Crear lead de prueba en Bitrix24» genera un lead real en tu CRM. Úsalo solo en entornos de prueba o con conocimiento del equipo.</p>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
            <button type="button" class="btn" wire:click="runBitrixSampleLead" wire:loading.attr="disabled" wire:target="runBitrixSampleLead,runConnectivity">Crear lead de prueba en Bitrix24</button>
            <button type="button" class="btn" wire:click="runConnectivity" wire:loading.attr="disabled" wire:target="runBitrixSampleLead,runConnectivity">Probar conectividad (Botmaker, Bitrix, cola)</button>
            <span wire:loading wire:target="runBitrixSampleLead,runConnectivity" class="muted" style="font-size: 0.9rem;">Ejecutando prueba…</span>
        </div>
        <p class="muted" style="margin: 0.75rem 0 0; font-size: 0.88rem;">La llamada a Bitrix puede tardar varios segundos (timeout de red). Si el mensaje no desaparece, revisa la consola del navegador (F12) y los logs de Laravel.</p>
        <p class="muted" style="margin: 0.35rem 0 0; font-size: 0.88rem;">Por consola equivalente: <code>php artisan bitrix:test-lead</code> y <code>php artisan webhook:status</code></p>
    </section>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 style="margin: 0 0 0.5rem; font-size: 1rem;">Endpoints JSON (misma sesión del panel)</h3>
        <p class="muted" style="margin: 0 0 0.5rem; font-size: 0.88rem;">POST requiere cabecera <code>X-XSRF-TOKEN</code> (cookie <code>XSRF-TOKEN</code>) y cookie de sesión. Útiles para Postman o scripts internos.</p>
        <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.9rem;">
            <li><code>POST {{ url('/monitor/integration-probes/bitrix-sample') }}</code> — crea lead de prueba; respuesta igual que abajo.</li>
            <li><code>GET {{ url('/monitor/integration-probes/connectivity') }}</code> — estado Botmaker, Bitrix, cola y resumen del día.</li>
        </ul>
    </section>

    @if ($bitrixResult)
        <section class="card card-pad" style="margin-bottom: 1rem; border-left: 4px solid {{ $bitrixResult['success'] ? '#16a34a' : '#dc2626' }};">
            <h3 style="margin: 0 0 0.5rem; font-size: 1rem;">Último resultado — lead Bitrix24</h3>
            @if (! empty($bitrixResult['config_warning']))
                <p style="color: #ca8a04; margin: 0 0 0.5rem;">{{ $bitrixResult['config_warning'] }}</p>
            @endif
            <p style="margin: 0 0 0.5rem;"><strong>HTTP {{ $bitrixResult['http_status'] }}</strong>
                @if (! empty($bitrixResult['lead_id']))
                    — Lead ID: <strong>{{ $bitrixResult['lead_id'] }}</strong>
                @endif
            </p>
            <details>
                <summary style="cursor: pointer;">Respuesta y campos enviados</summary>
                <pre style="margin-top: 0.5rem; overflow: auto; font-size: 0.78rem; max-height: 22rem;">{{ json_encode(['response_body' => $bitrixResult['body'], 'fields' => $bitrixResult['fields'] ?? []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </details>
        </section>
    @endif

    @if ($connectivityResult)
        <section class="card card-pad" style="margin-bottom: 1rem;">
            <h3 style="margin: 0 0 0.5rem; font-size: 1rem;">Conectividad</h3>
            <ul style="margin: 0; padding-left: 1.2rem;">
                <li>Botmaker API: @if($connectivityResult['botmaker']['ok']) <span class="badge-soft">OK</span> @else <span class="badge-soft" style="border-color: #dc2626;">{{ $connectivityResult['botmaker']['message'] }}</span> @endif</li>
                <li>Bitrix24 API: @if($connectivityResult['bitrix']['ok']) <span class="badge-soft">OK</span> @else <span class="badge-soft" style="border-color: #dc2626;">{{ $connectivityResult['bitrix']['message'] }}</span> @endif</li>
                <li>Cola / jobs atorados: @if($connectivityResult['queue']['ok']) <span class="badge-soft">OK</span> @else <span class="badge-soft" style="border-color: #ca8a04;">{{ $connectivityResult['queue']['message'] }}</span> @endif</li>
            </ul>
        </section>
    @endif
</div>
