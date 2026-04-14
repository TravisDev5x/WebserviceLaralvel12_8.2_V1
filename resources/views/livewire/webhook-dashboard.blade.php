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
            <h3 style="margin: 0.4rem 0 0; color: #1d4ed8;">{{ $totalToday }}</h3>
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
            <h3 style="margin: 0.4rem 0 0; color: #ca8a04;">{{ $pendingQueue }}</h3>
        </article>
    </div>

    <livewire:health-status />
    <livewire:connector-health-status />

    <div class="card card-pad" style="margin-top: 0.25rem;">
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
                        <th>Contacto</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($latestWebhooks->count() > 0)
                    @foreach ($latestWebhooks as $log)
                        <tr class="clickable-row" title="Clic para ver detalle" style="cursor: pointer;" data-href="{{ url('/monitor/logs/'.$log['id']) }}" onclick="window.location=this.dataset.href">
                            <td>
                                <span style="font-weight:700; color: {{ $log['direction_icon'] === '->' ? '#16a34a' : '#f97316' }};">{{ $log['direction_icon'] }}</span>
                                {{ $log['direction'] }}
                            </td>
                            <td>{{ $log['source_event'] }}</td>
                            <td>
                                <span class="badge-soft {{ $log['status_class'] }}">{{ $log['status_label'] }}</span>
                            </td>
                            <td>{{ $log['contact'] }}</td>
                            <td>{{ $log['created_at'] }}</td>
                        </tr>
                    @endforeach
                    @else
                        <tr>
                            <td colspan="5">No hay registros todavia.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div style="margin-top:.75rem;">
            {{ $latestWebhooks->links() }}
        </div>
    </div>
</div>
