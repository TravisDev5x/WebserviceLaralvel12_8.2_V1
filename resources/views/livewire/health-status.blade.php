<section class="card card-pad" wire:poll.30s>
    <h3 style="margin-top:0;">Estado de salud del middleware</h3>
    <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));">
        <div><strong>Base de datos:</strong> <span class="badge-soft" style="{{ $dbOk ? 'color:#065f46;background:#d1fae5;' : 'color:#991b1b;background:#fee2e2;' }}">{{ $dbOk ? 'OK' : 'ERROR' }}</span></div>
        <div><strong>Queue:</strong> <span class="badge-soft" style="{{ $queueStuck > 0 ? 'color:#92400e;background:#fef3c7;' : 'color:#065f46;background:#d1fae5;' }}">{{ $queueStuck > 0 ? "ATENCIÓN ({$queueStuck})" : 'OK' }}</span></div>
        <div><strong>Reintentos pendientes:</strong> {{ $pendingRetries }}</div>
        <div><strong>Último webhook:</strong> {{ $lastWebhookAt ?: '-' }}</div>
    </div>
</section>
