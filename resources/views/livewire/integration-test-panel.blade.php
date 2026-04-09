<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Pruebas de integración</h2>
            <p class="page-subtitle">Comprueba conectividad y simula los flujos bidireccionales Botmaker ↔ Bitrix24 (v2 Canal Abierto).</p>
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

    {{-- Connectivity --}}
    <section class="card card-pad" style="margin-bottom: 1rem;">
        <h3 style="margin:0 0 .6rem; font-size:1rem;">Conectividad (OAuth + imconnector + Botmaker + Cola)</h3>
        <div style="display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:.75rem;">
            <button type="button" class="btn btn-primary" wire:click="runConnectivity" wire:loading.attr="disabled" wire:target="runConnectivity">Probar todo</button>
            <span class="muted" wire:loading wire:target="runConnectivity">Ejecutando...</span>
        </div>

        @if ($connectivityResult)
            <ul style="margin:0; padding-left:1.2rem;">
                <li>Botmaker API: @if($connectivityResult['botmaker']['ok']) <span class="badge-soft">OK</span> @else <span class="badge-soft" style="border-color:#dc2626;">{{ $connectivityResult['botmaker']['message'] }}</span> @endif</li>
                <li>Bitrix24 (OAuth + imconnector): @if($connectivityResult['bitrix']['ok']) <span class="badge-soft">OK</span> @else <span class="badge-soft" style="border-color:#dc2626;">{{ $connectivityResult['bitrix']['message'] }}</span> @endif</li>
                <li>Cola: @if($connectivityResult['queue']['ok']) <span class="badge-soft">OK</span> @else <span class="badge-soft" style="border-color:#ca8a04;">{{ $connectivityResult['queue']['message'] }}</span> @endif</li>
            </ul>
        @endif
    </section>

    <div class="grid gap-3" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
        {{-- Flow A: Send test message to Open Channel --}}
        <section class="card card-pad">
            <h3 style="margin:0 0 .6rem; font-size:1rem;">Flujo A: Mensaje → Canal Abierto</h3>
            <small class="muted" style="display:block; margin-bottom:.75rem;">Envía un mensaje de prueba al Canal Abierto de Bitrix24 via <code>imconnector.send.messages</code>.</small>

            <div class="grid gap-2">
                <div>
                    <label>Teléfono (ID cliente)</label>
                    <input class="input" type="tel" wire:model.live="testPhone" placeholder="+5215512345678">
                    @error('testPhone') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label>Nombre del cliente</label>
                    <input class="input" type="text" wire:model.live="testName" placeholder="Juan Prueba">
                    @error('testName') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label>Mensaje</label>
                    <input class="input" type="text" wire:model.live="testMessageText" placeholder="Mensaje de prueba">
                    @error('testMessageText') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
                <button type="button" class="btn btn-primary" wire:click="sendTestChannelMessage" wire:loading.attr="disabled" wire:target="sendTestChannelMessage">Enviar al Canal Abierto</button>
                <span class="muted" wire:loading wire:target="sendTestChannelMessage">Enviando...</span>
            </div>
            @if($channelResult)
                <p style="margin:.5rem 0 0; font-size:.9rem; color: {{ $channelOk ? '#166534' : '#b91c1c' }};">{{ $channelResult }}</p>
            @endif
        </section>

        {{-- Flow B: Send test message via Botmaker --}}
        <section class="card card-pad">
            <h3 style="margin:0 0 .6rem; font-size:1rem;">Flujo B: Mensaje → Botmaker (WhatsApp)</h3>
            <small class="muted" style="display:block; margin-bottom:.75rem;">Envía un mensaje de prueba via <code>BotmakerService::sendMessage()</code> (dirección agente → cliente).</small>

            <div class="grid gap-2">
                <div>
                    <label>Teléfono destino</label>
                    <input class="input" type="tel" wire:model.live="botmakerTestPhone" placeholder="+5215512345678">
                    @error('botmakerTestPhone') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label>Mensaje</label>
                    <input class="input" type="text" wire:model.live="botmakerTestText" placeholder="Prueba flujo B">
                    @error('botmakerTestText') <small style="color:#dc2626;">{{ $message }}</small> @enderror
                </div>
                <button type="button" class="btn btn-primary" wire:click="sendTestBotmakerMessage" wire:loading.attr="disabled" wire:target="sendTestBotmakerMessage">Enviar a Botmaker</button>
                <span class="muted" wire:loading wire:target="sendTestBotmakerMessage">Enviando...</span>
            </div>
            @if($botmakerSendResult)
                <p style="margin:.5rem 0 0; font-size:.9rem; color: {{ $botmakerSendOk ? '#166534' : '#b91c1c' }};">{{ $botmakerSendResult }}</p>
            @endif
        </section>
    </div>

    {{-- Simulate full Flow A via HTTP --}}
    <section class="card card-pad" style="margin-top:1rem;">
        <h3 style="margin:0 0 .5rem; font-size:1rem;">Simular flujo completo (HTTP al propio middleware)</h3>
        <p class="muted" style="font-size:.85rem; margin:0 0 .75rem;">Las peticiones salen del servidor hacia <code>/api/webhook/botmaker</code> usando el mismo secreto configurado.</p>
        <div style="display:flex; flex-wrap:wrap; gap:.5rem;">
            <button type="button" class="btn" wire:click="simulateFlowBotmakerToBitrix" wire:loading.attr="disabled" wire:target="simulateFlowBotmakerToBitrix">Simular Flujo A (Botmaker → Canal Abierto)</button>
            <span class="muted" wire:loading wire:target="simulateFlowBotmakerToBitrix">Ejecutando...</span>
        </div>
        @if(count($flowSteps) > 0)
            <ol style="margin:.75rem 0 0; padding-left:1.2rem; font-size:.9rem;">
                @foreach($flowSteps as $step)
                    <li style="margin-bottom:.25rem;">{{ $step }}</li>
                @endforeach
            </ol>
        @endif
    </section>

    {{-- Test History --}}
    <section class="card card-pad" style="margin-top:1rem;">
        <h3 style="margin:0 0 .5rem; font-size:1rem;">Historial de pruebas (sesión)</h3>
        <ul style="margin:0; padding-left:1.1rem; font-size:.85rem;">
            @if(count($historyView) > 0)
                @foreach($historyView as $h)
                    <li style="margin-bottom:.25rem;">
                        <span class="muted">{{ $h['at'] }}</span> —
                        @if($h['ok'])<span style="color:#166534;">OK</span>@else<span style="color:#b91c1c;">Error</span>@endif
                        — {{ $h['text_short'] }}
                    </li>
                @endforeach
            @else
                <li class="muted">Sin historial en esta sesión.</li>
            @endif
        </ul>
    </section>

    <section class="card card-pad" style="margin-top:1rem;">
        <h3 style="margin:0 0 .5rem; font-size:1rem;">Endpoints JSON (sesión)</h3>
        <ul style="margin:0; padding-left:1.2rem; font-size:.88rem;">
            <li><code>GET {{ url('/monitor/integration-probes/connectivity') }}</code></li>
        </ul>
    </section>
</div>

<style>
    @media (max-width: 900px) {
        .grid[style*="repeat(2"] { grid-template-columns: 1fr !important; }
    }
</style>
