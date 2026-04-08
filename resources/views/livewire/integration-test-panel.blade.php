<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Pruebas de integración</h2>
            <p class="page-subtitle">Comprueba conectividad y simula webhooks del flujo Botmaker → Bitrix24.</p>
        </div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
            <button type="button" class="btn" wire:click="refreshSummary" wire:loading.attr="disabled">Actualizar resumen</button>
            <a class="btn" href="{{ url('/monitor/settings') }}">Volver al centro</a>
        </div>
    </div>

    @if ($summary)
        <section class="card card-pad" style="margin-bottom: 1rem;">
            <h3 style="margin: 0 0 0.6rem; font-size: 1rem;">Webhooks hoy</h3>
            <div class="grid-auto">
                <div><span class="muted">Total</span><br><strong>{{ $summary['today']['total'] }}</strong></div>
                <div><span class="muted">Enviados</span><br><strong>{{ $summary['today']['sent'] }}</strong></div>
                <div><span class="muted">Fallidos</span><br><strong>{{ $summary['today']['failed'] }}</strong></div>
                <div><span class="muted">En cola</span><br><strong>{{ $summary['today']['in_queue'] }}</strong></div>
                <div><span class="muted">Fallidos pendientes</span><br><strong>{{ $summary['failed_pending'] }}</strong></div>
            </div>
        </section>
    @endif

    <div class="grid gap-3" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
        <section class="card card-pad">
            <h3 style="margin:0 0 .6rem; font-size:1rem;">Botmaker</h3>
            <div style="display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:.75rem;">
                <button type="button" class="btn" wire:click="runConnectivity" wire:loading.attr="disabled" wire:target="runConnectivity">Probar conexión API</button>
            </div>
            <p class="muted" style="font-size:.85rem; margin:0 0 .75rem;">Usa la misma prueba global de conectividad (ver bloque inferior).</p>
        </section>

        <section class="card card-pad">
            <h3 style="margin:0 0 .6rem; font-size:1rem;">Bitrix24</h3>
            <div style="display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:.75rem;">
                <button type="button" class="btn" wire:click="runConnectivity" wire:loading.attr="disabled" wire:target="runConnectivity">Probar conexión CRM</button>
            </div>

            <h4 style="margin:.75rem 0 .35rem; font-size:.95rem;">Crear lead de prueba</h4>
            <div class="grid gap-2">
                <div>
                    <label>Nombre</label>
                    <input class="input" type="text" wire:model.live="bitrixLeadFirstName" placeholder="Juan">
                    <small class="muted">Nombre de contacto para lead de prueba. Ejemplo: <code>Juan</code>.</small>
                    @error('bitrixLeadFirstName') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label>Teléfono</label>
                    <input class="input" type="tel" wire:model.live="bitrixLeadPhone" placeholder="+5215512345678">
                    <small class="muted">Teléfono de lead para prueba en Bitrix24. Ejemplo: <code>+5215512345678</code>.</small>
                    @error('bitrixLeadPhone') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label>Título del lead</label>
                    <input class="input" type="text" wire:model.live="bitrixLeadTitle" placeholder="Prueba integración WhatsApp">
                    <small class="muted">Título visible del lead de prueba en CRM. Ejemplo: <code>Prueba integración WhatsApp</code>.</small>
                    @error('bitrixLeadTitle') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
                <button type="button" class="btn btn-primary" wire:click="createTestLead" wire:loading.attr="disabled">Crear</button>
            </div>
            @if($createLeadResult)
                <p style="margin:.5rem 0 0; font-size:.9rem;">{{ $createLeadResult }}</p>
            @endif

            <h4 style="margin:1rem 0 .35rem; font-size:.95rem;">Últimas pruebas</h4>
            <ul style="margin:0; padding-left:1.1rem; font-size:.85rem;">
                @if(count($bitrixHistoryView) > 0)
                @foreach($bitrixHistoryView as $h)
                    <li style="margin-bottom:.25rem;"><span class="muted">{{ $h['at'] }}</span> — @if($h['ok'])<span style="color:#166534;">OK</span>@else<span style="color:#b91c1c;">Error</span>@endif — {{ $h['text_short'] }}</li>
                @endforeach
                @else
                    <li class="muted">Sin historial en esta sesión.</li>
                @endif
            </ul>
        </section>
    </div>

    <section class="card card-pad" style="margin-top:1rem; border-left:4px solid #ca8a04;">
        <p style="margin:0 0 .75rem;"><strong>Importante:</strong> crear leads de prueba puede afectar tu CRM. Úsalo en entornos de prueba.</p>
        <div style="display:flex; flex-wrap:wrap; gap:.5rem;">
            <button type="button" class="btn" wire:click="runBitrixSampleLead" wire:loading.attr="disabled" wire:target="runBitrixSampleLead">Crear lead demo (mapeo completo)</button>
            <button type="button" class="btn" wire:click="runConnectivity" wire:loading.attr="disabled" wire:target="runConnectivity">Probar todo (Botmaker, Bitrix, cola)</button>
        </div>
        <span wire:loading wire:target="runBitrixSampleLead,runConnectivity" class="muted" style="font-size:.9rem;">Ejecutando…</span>
    </section>

    @if ($connectivityResult)
        <section class="card card-pad" style="margin-top:1rem;">
            <h3 style="margin:0 0 .5rem; font-size:1rem;">Conectividad</h3>
            <ul style="margin:0; padding-left:1.2rem;">
                <li>Botmaker API: @if($connectivityResult['botmaker']['ok']) <span class="badge-soft">OK</span> @else <span class="badge-soft" style="border-color:#dc2626;">{{ $connectivityResult['botmaker']['message'] }}</span> @endif</li>
                <li>Bitrix24 API: @if($connectivityResult['bitrix']['ok']) <span class="badge-soft">OK</span> @else <span class="badge-soft" style="border-color:#dc2626;">{{ $connectivityResult['bitrix']['message'] }}</span> @endif</li>
                <li>Cola: @if($connectivityResult['queue']['ok']) <span class="badge-soft">OK</span> @else <span class="badge-soft" style="border-color:#ca8a04;">{{ $connectivityResult['queue']['message'] }}</span> @endif</li>
            </ul>
        </section>
    @endif

    @if ($bitrixResult)
        <section class="card card-pad" style="margin-top:1rem; border-left:4px solid {{ $bitrixResult['success'] ? '#16a34a' : '#dc2626' }};">
            <h3 style="margin:0 0 .5rem; font-size:1rem;">Último resultado — lead demo Bitrix24</h3>
            @if (! empty($bitrixResult['config_warning']))
                <p style="color:#ca8a04; margin:0 0 .5rem;">{{ $bitrixResult['config_warning'] }}</p>
            @endif
            <p style="margin:0 0 .5rem;"><strong>HTTP {{ $bitrixResult['http_status'] }}</strong>
                @if (! empty($bitrixResult['lead_id'])) — Lead ID: <strong>{{ $bitrixResult['lead_id'] }}</strong> @endif
            </p>
            <details>
                <summary style="cursor:pointer;">Detalle</summary>
                <pre style="margin-top:.5rem; overflow:auto; font-size:.75rem; max-height:16rem;">{{ json_encode(['body' => $bitrixResult['body'] ?? '', 'fields' => $bitrixResult['fields'] ?? []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </details>
        </section>
    @endif

    <section class="card card-pad" style="margin-top:1rem;">
        <h3 style="margin:0 0 .5rem; font-size:1rem;">Simular flujo completo (HTTP al propio middleware)</h3>
        <p class="muted" style="font-size:.85rem; margin:0 0 .75rem;">Las peticiones salen del servidor hacia <code>/api/webhook/…</code> usando el mismo secreto configurado.</p>
        <div style="display:flex; flex-wrap:wrap; gap:.5rem;">
            <button type="button" class="btn" wire:click="simulateFlowBotmakerToBitrix" wire:loading.attr="disabled">Simular Flujo A (Botmaker → Bitrix24)</button>
        </div>
        @if(count($flowSteps) > 0)
            <ol style="margin:.75rem 0 0; padding-left:1.2rem; font-size:.9rem;">
                @foreach($flowSteps as $step)
                    <li style="margin-bottom:.25rem;">{{ $step }}</li>
                @endforeach
            </ol>
        @endif
    </section>

    <section class="card card-pad" style="margin-top:1rem;">
        <h3 style="margin:0 0 .5rem; font-size:1rem;">Endpoints JSON (sesión)</h3>
        <ul style="margin:0; padding-left:1.2rem; font-size:.88rem;">
            <li><code>POST {{ url('/monitor/integration-probes/bitrix-sample') }}</code></li>
            <li><code>GET {{ url('/monitor/integration-probes/connectivity') }}</code></li>
        </ul>
    </section>
</div>

<style>
    @media (max-width: 900px) {
        .grid[style*="repeat(2"] { grid-template-columns: 1fr !important; }
    }
</style>
