<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Registros de Webhooks</h2>
            <p class="page-subtitle">Filtros y trazabilidad completa de eventos.</p>
        </div>
        <div style="display:flex; gap:.5rem;">
            <button class="btn" wire:click="exportCsv" type="button" data-tooltip="Descargar resultados en CSV">
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><x-lucide-file-down class="size-4 shrink-0" aria-hidden="true" />Exportar CSV</span>
            </button>
            <button class="btn" wire:click="exportExcel" type="button" data-tooltip="Descargar resultados en Excel">
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><x-lucide-sheet class="size-4 shrink-0" aria-hidden="true" />Exportar Excel</span>
            </button>
        </div>
    </div>

    <div class="card card-pad" style="margin-bottom: 1rem;">
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.75rem;">
            <button type="button" class="btn btn-sm {{ $directionFilter === 'all' ? 'btn-primary' : '' }}" wire:click="$set('directionFilter','all')">Todas</button>
            <button type="button" class="btn btn-sm {{ $directionFilter === 'botmaker_to_bitrix' ? 'btn-primary' : '' }}" wire:click="$set('directionFilter','botmaker_to_bitrix')">Botmaker -> Bitrix24</button>
        </div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.75rem;">
            @foreach ($statuses as $value => $label)
                <button type="button" class="badge-soft @if($statusFilter === $value) btn-primary @endif" wire:click="$set('statusFilter','{{ $value }}')">{{ $label }}</button>
            @endforeach
        </div>
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <div>
                <label for="search">Buscar por teléfono o nombre</label>
                <input id="search" class="input" type="text" wire:model.live.debounce.400ms="search" placeholder="Ej. 5544... o Juan">
                <small class="muted">Filtra por nombre, teléfono o referencia del contacto. Ejemplo: <code>5512345678</code> o <code>Juan</code>.</small>
            </div>
            <div>
                <label for="dateFrom">Desde</label>
                <input id="dateFrom" class="input" type="date" wire:model.live="dateFrom">
                <small class="muted">Fecha inicial del rango de consulta.</small>
            </div>
            <div>
                <label for="dateTo">Hasta</label>
                <input id="dateTo" class="input" type="date" wire:model.live="dateTo">
                <small class="muted">Fecha final del rango de consulta.</small>
            </div>
        </div>
    </div>

    <div class="card card-pad">
        <div class="table-wrap">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dirección</th>
                        <th>Evento</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th>Resultado</th>
                        <th>Procesamiento ms</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($webhooks->count() > 0)
                    @foreach ($webhooks as $webhook)
                        <tr class="clickable-row" style="cursor: pointer;" data-href="{{ url('/monitor/logs/'.$webhook->id) }}" onclick="window.location=this.dataset.href">
                            <td>{{ $webhook->id }}</td>
                            <td>{{ $webhook->direction }}</td>
                            <td>{{ $webhook->source_event }}</td>
                            <td>{{ $webhook->external_id ?: '-' }}</td>
                            <td>{{ $webhook->status }}</td>
                            <td>{{ $webhook->error_message ?: 'OK' }}</td>
                            <td>{{ $webhook->processing_ms ?: '-' }}</td>
                            <td>{{ $webhook->created_at?->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    @endforeach
                    @else
                        <tr>
                            <td colspan="8">No hay registros para los filtros seleccionados.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $webhooks->links() }}
        </div>
    </div>
</div>
