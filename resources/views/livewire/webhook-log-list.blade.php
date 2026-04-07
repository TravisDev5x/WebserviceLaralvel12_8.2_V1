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
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.75rem;">
            <button type="button" class="btn btn-sm {{ $directionFilter === 'all' ? 'btn-primary' : '' }}" wire:click="$set('directionFilter','all')">Todas</button>
            <button type="button" class="btn btn-sm {{ $directionFilter === 'botmaker_to_bitrix' ? 'btn-primary' : '' }}" wire:click="$set('directionFilter','botmaker_to_bitrix')">WhatsApp -> CRM</button>
            <button type="button" class="btn btn-sm {{ $directionFilter === 'bitrix_to_botmaker' ? 'btn-primary' : '' }}" wire:click="$set('directionFilter','bitrix_to_botmaker')">CRM -> WhatsApp</button>
        </div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.75rem;">
            @foreach ($statuses as $value => $label)
                @php($isActive = $statusFilter === $value)
                @php($badgeColor = match($value){'sent'=>'#16a34a','failed'=>'#dc2626','processing'=>'#ca8a04','received'=>'#2563eb',default=>'#64748b'})
                <button type="button" class="badge-soft" style="border-color:{{ $badgeColor }}; color:{{ $isActive ? '#fff' : $badgeColor }}; background:{{ $isActive ? $badgeColor : 'transparent' }};" wire:click="$set('statusFilter','{{ $value }}')">{{ $label }}</button>
            @endforeach
        </div>
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <div>
                <label for="search">Buscar por teléfono o nombre</label>
                <input id="search" class="input" type="text" wire:model.live.debounce.400ms="search" placeholder="Ej. 5544... o Juan">
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
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th>Resultado</th>
                        <th>Procesamiento ms</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($webhooks as $webhook)
                        @php
                            $payload = is_array($webhook->payload_in) ? $webhook->payload_in : [];
                            $contactName = trim((string) (($payload['firstName'] ?? '').' '.($payload['lastName'] ?? '')));
                            $contactPhone = (string) ($payload['whatsappNumber'] ?? $payload['contact']['phone'] ?? $payload['phone'] ?? '');
                            $contact = $contactName !== '' ? $contactName : ($contactPhone !== '' ? $contactPhone : ($webhook->external_id ?: '-'));
                            $resultText = $webhook->status === 'sent'
                                ? 'Lead creado ✓'
                                : ($webhook->error_message ? 'Error: '.\Illuminate\Support\Str::limit($webhook->error_message, 42) : (($webhook->http_status ? "HTTP {$webhook->http_status}" : 'Sin respuesta')));
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
                            <td>{{ $contact }}</td>
                            <td><span style="{{ $badgeStyle }}">{{ $webhook->status }}</span></td>
                            <td>{{ $resultText }}</td>
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
