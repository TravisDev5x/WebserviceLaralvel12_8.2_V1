<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Registros de Webhooks</h2>
            <p class="page-subtitle">Filtros y trazabilidad completa de eventos.</p>
        </div>
        <div style="display:flex; gap:.5rem;">
            <button class="btn" wire:click="exportCsv" type="button" data-tooltip="Descargar resultados en CSV">
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><i data-lucide="file-down"></i>Exportar CSV</span>
            </button>
            <button class="btn" wire:click="exportExcel" type="button" data-tooltip="Descargar resultados en Excel">
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><i data-lucide="sheet"></i>Exportar Excel</span>
            </button>
        </div>
    </div>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <div>
                <label for="directionFilter">Dirección</label>
                <select id="directionFilter" class="select" wire:model.live="directionFilter">
                    @foreach ($directions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="statusFilter">Estado</label>
                <select id="statusFilter" class="select" wire:model.live="statusFilter">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dateFrom">Desde</label>
                <input id="dateFrom" class="input" type="date" wire:model.live="dateFrom">
            </div>
            <div>
                <label for="dateTo">Hasta</label>
                <input id="dateTo" class="input" type="date" wire:model.live="dateTo">
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
                        <th>ID Externo</th>
                        <th>Estado</th>
                        <th>Código HTTP</th>
                        <th>Procesamiento ms</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($webhooks as $webhook)
                        @php
                            $badgeStyle = match ($webhook->status) {
                                'sent' => 'background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 12px;',
                                'failed' => 'background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 12px;',
                                'processing' => 'background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 12px;',
                                default => 'background: #dbeafe; color: #1e3a8a; padding: 2px 8px; border-radius: 12px;',
                            };
                        @endphp
                        <tr class="clickable-row" style="cursor: pointer;" onclick="window.location='{{ url('/monitor/logs/'.$webhook->id) }}'">
                            <td>{{ $webhook->id }}</td>
                            <td>{{ $webhook->direction }}</td>
                            <td>{{ $webhook->source_event }}</td>
                            <td>{{ $webhook->external_id ?: '-' }}</td>
                            <td><span style="{{ $badgeStyle }}">{{ $webhook->status }}</span></td>
                            <td>{{ $webhook->http_status ?: '-' }}</td>
                            <td>{{ $webhook->processing_ms ?: '-' }}</td>
                            <td>{{ $webhook->created_at?->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No hay registros para los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $webhooks->links() }}
        </div>
    </section>
</div>
