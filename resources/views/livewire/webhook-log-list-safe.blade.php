<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Registros de Webhooks</h2>
            <p class="page-subtitle">Vista segura de historial y filtros.</p>
        </div>
        <div style="display:flex; gap:.5rem;">
            <button class="btn" wire:click="exportCsv" type="button">Exportar CSV</button>
            <button class="btn" wire:click="exportExcel" type="button">Exportar Excel</button>
        </div>
    </div>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <div>
                <label for="searchSafe">Buscar</label>
                <input id="searchSafe" class="input" type="text" wire:model.live.debounce.400ms="search" placeholder="Teléfono o nombre">
            </div>
            <div>
                <label for="dateFromSafe">Desde</label>
                <input id="dateFromSafe" class="input" type="date" wire:model.live="dateFrom">
            </div>
            <div>
                <label for="dateToSafe">Hasta</label>
                <input id="dateToSafe" class="input" type="date" wire:model.live="dateTo">
            </div>
        </div>
    </section>

    <section class="card card-pad">
        <div class="table-wrap">
            <table class="table-clean">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Dirección</th>
                    <th>Evento</th>
                    <th>Estado</th>
                    <th>HTTP</th>
                    <th>Error</th>
                    <th>Fecha</th>
                </tr>
                </thead>
                <tbody>
                @if ($webhooks->count() > 0)
                @foreach ($webhooks as $webhook)
                    <tr class="clickable-row" style="cursor:pointer;" onclick="window.location='{{ url('/monitor/logs/'.$webhook->id) }}'">
                        <td>{{ $webhook->id }}</td>
                        <td>{{ $webhook->direction }}</td>
                        <td>{{ $webhook->source_event }}</td>
                        <td>{{ $webhook->status }}</td>
                        <td>{{ $webhook->http_status ?: '-' }}</td>
                        <td>{{ $webhook->error_message }}</td>
                        <td>{{ $webhook->created_at?->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @endforeach
                @else
                    <tr><td colspan="7">No hay registros para los filtros seleccionados.</td></tr>
                @endif
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $webhooks->links() }}
        </div>
    </section>
</div>
