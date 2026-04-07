<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Webhooks Fallidos</h2>
            <p class="page-subtitle">
                @if ($canManageFailed)
                    Gestión manual de reintentos y resolución.
                @else
                    Solo consulta de fallos; los reintentos los gestiona un operador o administrador.
                @endif
            </p>
        </div>
        @if ($canManageFailed)
            <button type="button" class="btn btn-primary" wire:click="retryAllPending" onclick="return confirm('Se reintentaran los webhooks pendientes. ¿Continuar?')">Reintentar todos los pendientes</button>
        @endif
    </div>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <label for="statusFilter">Estado</label>
        <select id="statusFilter" class="select" wire:model.live="statusFilter" style="max-width: 320px;">
            @if (count($statuses) > 0)
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            @else
                <option value="all">Todos</option>
            @endif
        </select>
        <small class="muted" style="display:block; margin-top:.35rem;">Filtra fallos por estado de reintento. Ejemplo: "pending" para ver pendientes de recuperación.</small>
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
                        <tr class="clickable-row">
                            <td>{{ $failedWebhook->id }}</td>
                            <td>{{ $failedWebhook->direction }}</td>
                            <td>{{ $this->failedWebhookLeadId($failedWebhook) }}</td>
                            <td>{{ $failedWebhook->attempts }}</td>
                            <td>{{ $failedWebhook->max_attempts }}</td>
                            <td><span style="{{ $this->failedWebhookStatusStyle($failedWebhook) }}">{{ $failedWebhook->status }}</span></td>
                            <td><span data-tooltip="{{ $failedWebhook->last_error }}">{{ $this->failedWebhookFriendlyError($failedWebhook) }}</span></td>
                            <td>{{ $failedWebhook->next_retry_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                            <td>{{ $failedWebhook->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <div style="width:100%; margin-bottom:.3rem;">
                                    <small class="muted">Intento {{ $failedWebhook->attempts }} de {{ $failedWebhook->max_attempts }}</small>
                                    <div style="height:.35rem; background:#e5e7eb; border-radius:999px; overflow:hidden;">
                                        <div style="height:100%; width:{{ $this->failedWebhookProgressPercent($failedWebhook) }}%; background:#f97316;"></div>
                                    </div>
                                </div>
                                @if ($canManageFailed)
                                    <button class="btn btn-sm" type="button" wire:click="forceRetry({{ $failedWebhook->id }})" onclick="return confirm('¿Reintentar este webhook ahora?')" data-tooltip="Enviar nuevamente este webhook" @if (! $this->failedWebhookRowCanRetry($failedWebhook)) disabled @endif>
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
