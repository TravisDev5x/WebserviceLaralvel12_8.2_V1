<section class="card card-pad" wire:poll.30s>
    <h3 style="margin-top:0;">Estado de salud del middleware</h3>
    <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
        <div style="display:flex; gap:.6rem; align-items:center;">
            <span class="health-big {{ $botmakerOk ? 'ok pulse' : 'bad' }}"></span>
            <div>
                <strong>Botmaker:</strong> {!! $botmakerOk ? 'conectado ✓' : 'sin conexión — Revisar configuración' !!}
            </div>
        </div>
        <div style="display:flex; gap:.6rem; align-items:center;">
            <span class="health-big {{ $bitrixOk ? 'ok pulse' : 'bad' }}"></span>
            <div>
                <strong>Bitrix24:</strong> {!! $bitrixOk ? 'conectado ✓' : 'sin conexión — Revisar configuración' !!}
            </div>
        </div>
        <div style="display:flex; gap:.6rem; align-items:center;">
            <span class="health-big {{ $queueStuck > 0 ? 'warn' : 'ok pulse' }}"></span>
            <div>
                <strong>Cola:</strong> {!! $queueStuck > 0 ? "con retraso ({$queueStuck}) — Revisar worker" : 'activa ✓' !!}
            </div>
        </div>
    </div>
    <p class="muted" style="margin:.75rem 0 0;">Último webhook: {{ $lastWebhookAt ?: '-' }}</p>
</section>
<style>
    .health-big{width:1rem;height:1rem;border-radius:999px;display:inline-block}
    .health-big.ok{background:#16a34a}
    .health-big.bad{background:#dc2626}
    .health-big.warn{background:#ca8a04}
    .pulse{animation:pulse-dot 1.6s infinite}
    @keyframes pulse-dot{0%{box-shadow:0 0 0 0 rgba(22,163,74,.45)}100%{box-shadow:0 0 0 12px rgba(22,163,74,0)}}
</style>
