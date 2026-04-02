<div wire:poll.10s>
    <div class="page-header">
        <div>
            <h2 class="page-title">Tablero de Monitoreo</h2>
            <p class="page-subtitle">Vista en tiempo real de actividad de webhooks.</p>
        </div>
        <span class="badge-soft">Actualización automática cada 10s</span>
    </div>

    <div class="grid-auto" style="margin-bottom: 1.25rem;">
        <article class="card kpi-card card-pad">
            <p class="muted" style="margin: 0;">Total webhooks hoy</p>
            <h3 style="margin: 0.4rem 0 0;">{{ $totalToday }}</h3>
        </article>
        <article class="card kpi-card card-pad">
            <p class="muted" style="margin: 0;">Exitosos hoy</p>
            <h3 style="margin: 0.4rem 0 0; color: #166534;">{{ $successToday }}</h3>
        </article>
        <article class="card kpi-card card-pad">
            <p class="muted" style="margin: 0;">Fallidos hoy</p>
            <h3 style="margin: 0.4rem 0 0; color: #b91c1c;">{{ $failedToday }}</h3>
        </article>
        <article class="card kpi-card card-pad">
            <p class="muted" style="margin: 0;">Pendientes en cola</p>
            <h3 style="margin: 0.4rem 0 0; color: #92400e;">{{ $pendingQueue }}</h3>
        </article>
    </div>

    <livewire:health-status />

    <section class="card card-pad" style="margin-top: 0.25rem;">
        <div class="page-header" style="margin-bottom: 0.75rem;">
            <h3 class="page-title">Webhooks recientes</h3>
            <a class="btn btn-sm" href="{{ url('/monitor/logs') }}">Ver todos</a>
        </div>
        <div class="table-wrap">
            <table class="table-clean" style="min-width: 660px;">
                <thead>
                    <tr>
                        <th>Dirección</th>
                        <th>Evento</th>
                        <th>Estado</th>
                        <th>Lead ID</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestWebhooks as $log)
                        <tr class="clickable-row" style="cursor: pointer;" onclick="window.location='{{ url('/monitor/logs/'.$log['id']) }}'">
                            <td>{{ $log['direction'] }}</td>
                            <td>{{ $log['source_event'] }}</td>
                            <td><span class="badge">{{ $log['status'] }}</span></td>
                            <td>{{ $log['lead_id'] }}</td>
                            <td>{{ $log['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No hay registros todavia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:.75rem;">
            {{ $latestWebhooks->links() }}
        </div>
    </section>
</div>
