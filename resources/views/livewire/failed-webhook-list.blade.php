<div>
    <h2>Webhooks Fallidos</h2>

    @if($canManageFailed)
        <button type="button" class="btn btn-primary" wire:click="retryAllPending">Reintentar todos los pendientes</button>
    @endif

    <select wire:model.live="statusFilter" class="select">
        @if(count($statuses) > 0)
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        @else
            <option value="all">Todos</option>
        @endif
    </select>

    <table class="table-clean">
        <thead>
            <tr>
                <th>ID</th>
                <th>Dirección</th>
                <th>ID Lead</th>
                <th>Estado</th>
                <th>Intentos</th>
                <th>Último Error</th>
                <th>Próximo Reintento</th>
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

    {{ $failedWebhooks->links() }}
</div>
