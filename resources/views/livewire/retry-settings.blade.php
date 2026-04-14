<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Reintentos y rendimiento</h2>
            <p class="page-subtitle">Controla cuántas veces se reintenta un webhook fallido y el tiempo de espera de las llamadas HTTP salientes.</p>
        </div>
        <a class="btn" href="{{ url('/monitor/settings') }}">Volver al centro</a>
    </div>

    @if ($successMessage)
        <div class="alert mb-4" role="status">
            <h2 class="text-base font-semibold m-0">{{ $successMessage }}</h2>
        </div>
    @endif
    @if ($errorMessage)
        <div class="alert-destructive mb-4" role="alert">
            <h2 class="text-base font-semibold m-0">{{ $errorMessage }}</h2>
        </div>
    @endif

    <div class="card card-pad">
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div>
                <label for="retry-max">Máximo de intentos</label>
                <input id="retry-max" class="input" type="number" min="1" max="10" wire:model.live="retryMaxAttempts" placeholder="5">
                <small class="muted field-help">Cantidad de reintentos antes de marcar agotado en Webhooks Fallidos. Ejemplo: <code>5</code>.</small>
                @error('retryMaxAttempts') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="retry-backoff">Backoff (segundos, separados por coma)</label>
                <input id="retry-backoff" class="input" type="text" wire:model.live="retryBackoffSchedule" placeholder="30,60,300,900,3600">
                <small class="muted field-help">Intervalos entre reintentos. Ejemplo: <code>30,60,300,900,3600</code> (30s, 1m, 5m, 15m, 1h).</small>
                @error('retryBackoffSchedule') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="retry-timeout">Timeout HTTP (segundos)</label>
                <input id="retry-timeout" class="input" type="number" min="5" max="120" wire:model.live="retryHttpTimeout" placeholder="15">
                <small class="muted field-help">Tiempo máximo de espera antes de considerar fallo HTTP. Ejemplo recomendado: <code>15</code>.</small>
                @error('retryHttpTimeout') <small style="color:#dc2626;">{{ $message }}</small> @enderror
            </div>
        </div>
        <button type="button" class="btn btn-primary" style="margin-top:1rem;" wire:click="save">Guardar</button>
    </div>
</div>

<style>
    .field-hint { margin: .35rem 0 0; font-size: .82rem; }
.field-help { display:block; margin:.35rem 0 0; font-size:.8rem; line-height:1.4; }
</style>
