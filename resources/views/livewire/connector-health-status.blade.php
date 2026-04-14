<div class="card card-pad" wire:poll.60s>
    <h3 style="margin-top:0;">Estado del conector imconnector (v2)</h3>
    <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
        <div style="display:flex; gap:.6rem; align-items:center;">
            <span class="health-big {{ $oauthOk ? 'ok pulse' : 'warn' }}"></span>
            <div>
                <strong>OAuth Bitrix24:</strong> {{ $oauthMessage }}
            </div>
        </div>
        <div style="display:flex; gap:.6rem; align-items:center;">
            <span class="health-big {{ $connectorOk ? 'ok pulse' : 'warn' }}"></span>
            <div>
                <strong>Conector Canal Abierto:</strong> {{ $connectorMessage }}
            </div>
        </div>
        <div style="display:flex; gap:.6rem; align-items:center;">
            <span class="health-big {{ $botmakerSendOk ? 'ok pulse' : 'warn' }}"></span>
            <div>
                <strong>Botmaker Envío:</strong> {{ $botmakerSendMessage }}
            </div>
        </div>
    </div>
</div>
