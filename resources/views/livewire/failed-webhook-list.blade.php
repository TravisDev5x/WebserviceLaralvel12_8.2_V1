<div>
    @php($canManageFailed = user_can('failed.manage'))
    <div class="page-header">
        <div>
            <h2 class="page-title">Webhooks Fallidos</h2>
            <p class="page-subtitle">@if($canManageFailed) Gestión manual de reintentos y resolución. @else Solo consulta de fallos; los reintentos los gestiona un operador o administrador. @endif</p>
        </div>
        @if($canManageFailed)
            <button type="button" class="btn btn-primary" wire:click="retryAllPending" onclick="return confirm('Se reintentaran los webhooks pendientes. ¿Continuar?')">Reintentar todos los pendientes</button>
        @endif
    </div>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <label for="statusFilter">Estado</label>
        <select id="statusFilter" class="select" wire:model.live="statusFilter" style="max-width: 320px;">
            @forelse ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @empty
                <option value="all">Todos</option>
            @endforelse
        </select>
    </section>

    <section class="card card-pad">
        <div class="table-wrap">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dirección</th>
                        <th>ID del Lead</th>
                        <th>Intentos</th>
                        <th>Intentos Máximos</th>
                        <th>Estado</th>
                        <th>Último Error</th>
                        <th>Próximo Reintento</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($failedWebhooks as $failedWebhook)
                        @php
                            $leadId = $failedWebhook->webhookLog?->external_id
                                ?? ($failedWebhook->payload['data']['FIELDS']['ID'] ?? '-');
                            $lastError = $failedWebhook->last_error ? \Illuminate\Support\Str::limit($failedWebhook->last_error, 90) : '-';
                            $friendlyError = match (true) {
                                str_contains(strtolower((string) $failedWebhook->last_error), '401') => 'Token rechazado — verificar permisos en Botmaker',
                                str_contains(strtolower((string) $failedWebhook->last_error), 'timed out') => 'No se pudo conectar — el servicio puede estar caído',
                                str_contains(strtolower((string) $failedWebhook->last_error), '500') => 'Error interno del servicio destino',
                                default => $lastError,
                            };
                            $canRetry = in_array($failedWebhook->status, ['pending', 'exhausted'], true);
                            $progress = $failedWebhook->max_attempts > 0 ? min(100, (int) (($failedWebhook->attempts / $failedWebhook->max_attempts) * 100)) : 0;
                            $statusStyle = match ($failedWebhook->status) {
                                'resolved' => 'background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 999px;',
                                'retrying' => 'background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 999px;',
                                'exhausted' => 'background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 999px;',
                                default => 'background: #dbeafe; color: #1e3a8a; padding: 2px 8px; border-radius: 999px;',
                            };
                        @endphp
                        <tr class="clickable-row">
                            <td>{{ $failedWebhook->id }}</td>
                            <td>{{ $failedWebhook->direction }}</td>
                            <td>{{ $leadId }}</td>
                            <td>{{ $failedWebhook->attempts }}</td>
                            <td>{{ $failedWebhook->max_attempts }}</td>
                            <td><span style="{{ $statusStyle }}">{{ $failedWebhook->status }}</span></td>
                            <td><span data-tooltip="{{ $failedWebhook->last_error }}">{{ $friendlyError }}</span></td>
                            <td>{{ $failedWebhook->next_retry_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                            <td>{{ $failedWebhook->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <div style="width:100%; margin-bottom:.3rem;">
                                    <small class="muted">Intento {{ $failedWebhook->attempts }} de {{ $failedWebhook->max_attempts }}</small>
                                    <div style="height:.35rem; background:#e5e7eb; border-radius:999px; overflow:hidden;">
                                        <div style="height:100%; width:{{ $progress }}%; background:#f97316;"></div>
                                    </div>
                                </div>
                                @if($canManageFailed)
                                    <button class="btn btn-sm" type="button" wire:click="forceRetry({{ $failedWebhook->id }})" onclick="return confirm('¿Reintentar este webhook ahora?')" data-tooltip="Enviar nuevamente este webhook" @disabled(! $canRetry)>
                                        <span style="display:inline-flex; align-items:center; gap:.3rem;"><i data-lucide="rotate-cw"></i>Forzar reintento</span>
                                    </button>
                                    <button class="btn btn-sm" type="button" wire:click="markResolved({{ $failedWebhook->id }})" data-tooltip="Marcar como resuelto manualmente">
                                        <span style="display:inline-flex; align-items:center; gap:.3rem;"><i data-lucide="check-circle2"></i>Marcar resuelto</span>
                                    </button>
                                @else
                                    <span class="muted" style="font-size:0.85rem;">Solo lectura</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">No hay registros de webhooks fallidos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $failedWebhooks->links() }}
        </div>
    </section>
</div>
