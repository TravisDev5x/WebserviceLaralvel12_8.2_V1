<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Webhooks fallidos</h2>
            <p class="page-subtitle">Revisa errores y reintentos pendientes del flujo Botmaker → Bitrix24.</p>
        </div>
        @if($canManageFailed)
            <button type="button" class="btn btn-primary" wire:click="retryAllPending">Reintentar pendientes</button>
        @endif
    </div>

    <div class="card card-pad" style="margin-bottom: 1rem;">
        <label for="failed-status-filter">Filtrar por estado</label>
        <select id="failed-status-filter" wire:model.live="statusFilter" class="select">
            @if(count($statuses) > 0)
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            @endif
        </select>
    </div>

    <div class="card card-pad">
        <div class="table-wrap">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dirección</th>
                        <th>Lead</th>
                        <th>Estado</th>
                        <th>Intentos</th>
                        <th>Último error</th>
                        <th>Próximo reintento</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @if($failedWebhooks->count() > 0)
                        @foreach($failedWebhooks as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->direction }}</td>
                                <td>{{ $item->lead_id }}</td>
                                <td><span class="badge-soft {{ $item->status_class }}">{{ $item->status }}</span></td>
                                <td>{{ $item->attempts }} / {{ $item->max_attempts }}</td>
                                <td>{{ $item->last_error }}</td>
                                <td>{{ $item->next_retry_at }}</td>
                                <td>{{ $item->created_at }}</td>
                                <td>
                                    @if($canManageFailed)
                                        <button type="button" class="btn btn-sm" wire:click="forceRetry({{ $item->id }})" @disabled(! $item->can_retry)>Reintentar</button>
                                        <button type="button" class="btn btn-sm" wire:click="markResolved({{ $item->id }})">Resolver</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9">No hay webhooks fallidos.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div style="margin-top:.75rem;">
            {{ $failedWebhooks->links() }}
        </div>
    </div>
</div>
