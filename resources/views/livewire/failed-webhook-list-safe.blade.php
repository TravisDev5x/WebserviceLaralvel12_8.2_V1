<div>
    @php($canManageFailed = user_can('failed.manage'))
    <div class="page-header">
        <div>
            <h2 class="page-title">Webhooks Fallidos</h2>
            <p class="page-subtitle">Vista segura de fallos y reintentos.</p>
        </div>
        @if($canManageFailed)
            <button type="button" class="btn btn-primary" wire:click="retryAllPending">Reintentar todos</button>
        @endif
    </div>

    <section class="card card-pad" style="margin-bottom: 1rem;">
        <label for="statusFilterSafe">Estado</label>
        <select id="statusFilterSafe" class="select" wire:model.live="statusFilter" style="max-width: 320px;">
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
                    <th>Lead</th>
                    <th>Intentos</th>
                    <th>Máx.</th>
                    <th>Estado</th>
                    <th>Error</th>
                    <th>Próximo reintento</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($failedWebhooks as $failedWebhook)
                    @php($leadId = $failedWebhook->webhookLog?->external_id ?? ($failedWebhook->payload['data']['FIELDS']['ID'] ?? '-'))
                    @php($canRetry = in_array($failedWebhook->status, ['pending', 'exhausted'], true))
                    <tr>
                        <td>{{ $failedWebhook->id }}</td>
                        <td>{{ $failedWebhook->direction }}</td>
                        <td>{{ $leadId }}</td>
                        <td>{{ $failedWebhook->attempts }}</td>
                        <td>{{ $failedWebhook->max_attempts }}</td>
                        <td>{{ $failedWebhook->status }}</td>
                        <td>{{ \Illuminate\Support\Str::limit((string) $failedWebhook->last_error, 80) }}</td>
                        <td>{{ $failedWebhook->next_retry_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
                        <td>{{ $failedWebhook->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td style="display:flex; gap:.35rem; flex-wrap:wrap;">
                            @if($canManageFailed)
                                <button class="btn btn-sm" type="button" wire:click="forceRetry({{ $failedWebhook->id }})" @disabled(! $canRetry)>Reintentar</button>
                                <button class="btn btn-sm" type="button" wire:click="markResolved({{ $failedWebhook->id }})">Resolver</button>
                            @else
                                <span class="muted">Solo lectura</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10">No hay registros de webhooks fallidos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $failedWebhooks->links() }}
        </div>
    </section>
</div>
