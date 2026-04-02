<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Configuración de Integraciones</h2>
            <p class="page-subtitle">Edita URLs y tokens de Botmaker/Bitrix24 sin tocar el código.</p>
        </div>
    </div>

    @if ($successMessage)
        <section class="card card-pad" style="margin-bottom: 1rem; border-left: 4px solid #16a34a;">
            <p style="margin: 0;">{{ $successMessage }}</p>
        </section>
    @endif

    @if ($errorMessage)
        <section class="card card-pad" style="margin-bottom: 1rem; border-left: 4px solid #dc2626;">
            <p style="margin: 0;">{{ $errorMessage }}</p>
        </section>
    @endif

    <section class="card card-pad">
        <form wire:submit="save">
            <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                <div>
                    <label for="botmakerApiUrl">Botmaker API URL</label>
                    <input id="botmakerApiUrl" class="input" type="url" wire:model.live="botmakerApiUrl" placeholder="https://go.botmaker.com/api/v1.0">
                    @error('botmakerApiUrl') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label for="bitrix24WebhookUrl">URL del Webhook de Bitrix24</label>
                    <input id="bitrix24WebhookUrl" class="input" type="url" wire:model.live="bitrix24WebhookUrl" placeholder="https://tu-dominio.bitrix24.com/rest/...">
                    @error('bitrix24WebhookUrl') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label for="botmakerApiToken">Token API de Botmaker</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input id="botmakerApiToken" class="input" type="password" wire:model.live="botmakerApiToken" placeholder="Token de Botmaker">
                        <button type="button" class="btn" data-toggle-password="botmakerApiToken" aria-label="Mostrar u ocultar Botmaker API Token">Ver</button>
                    </div>
                    @error('botmakerApiToken') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label for="botmakerWebhookSecret">Secreto de Webhook de Botmaker</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input id="botmakerWebhookSecret" class="input" type="password" wire:model.live="botmakerWebhookSecret" placeholder="Secreto de firma Botmaker">
                        <button type="button" class="btn" data-toggle-password="botmakerWebhookSecret" aria-label="Mostrar u ocultar secreto de webhook de Botmaker">Ver</button>
                    </div>
                    @error('botmakerWebhookSecret') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label for="bitrix24WebhookSecret">Secreto de Webhook de Bitrix24</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input id="bitrix24WebhookSecret" class="input" type="password" wire:model.live="bitrix24WebhookSecret" placeholder="Secreto de firma Bitrix24">
                        <button type="button" class="btn" data-toggle-password="bitrix24WebhookSecret" aria-label="Mostrar u ocultar secreto de webhook de Bitrix24">Ver</button>
                    </div>
                    @error('bitrix24WebhookSecret') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label for="botmakerSalaryCurrency">Moneda para salario (ISO)</label>
                    <input id="botmakerSalaryCurrency" class="input" type="text" maxlength="3" wire:model.live="botmakerSalaryCurrency" placeholder="MXN">
                    @error('botmakerSalaryCurrency') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                </div>
            </div>

            <div style="margin-top: 1rem;">
                <h3 style="margin: 0 0 0.45rem;">Mapeo Botmaker -> Bitrix (avanzado)</h3>
                <p class="muted" style="margin: 0 0 0.75rem; font-size: 0.88rem;">Puedes editar alias de entrada, campos destino y catálogos sin tocar PHP.</p>
            </div>

            <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
                <div>
                    <label for="botmakerSourceAliasesJson">Alias de origen (JSON)</label>
                    <textarea id="botmakerSourceAliasesJson" class="textarea" wire:model.live="botmakerSourceAliasesJson" rows="14" style="font-family: Consolas, monospace;"></textarea>
                    @error('botmakerSourceAliasesJson') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                </div>
                <div>
                    <label for="botmakerBitrixFieldsJson">Campos Bitrix destino (JSON)</label>
                    <textarea id="botmakerBitrixFieldsJson" class="textarea" wire:model.live="botmakerBitrixFieldsJson" rows="14" style="font-family: Consolas, monospace;"></textarea>
                    @error('botmakerBitrixFieldsJson') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                </div>
            </div>

            <div style="margin-top: 0.75rem;">
                <label for="botmakerEnumMapsJson">Catálogos (JSON etiqueta -> ID Bitrix)</label>
                <textarea id="botmakerEnumMapsJson" class="textarea" wire:model.live="botmakerEnumMapsJson" rows="14" style="font-family: Consolas, monospace;"></textarea>
                @error('botmakerEnumMapsJson') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </div>

            <div style="margin-top: 1rem;">
                <h3 style="margin: 0 0 0.45rem;">Configuración de reintentos</h3>
                <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                    <div>
                        <label for="retryMaxAttempts">Máximo de intentos</label>
                        <input id="retryMaxAttempts" class="input" type="number" min="1" max="10" wire:model.live="retryMaxAttempts">
                        @error('retryMaxAttempts') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                    </div>
                    <div>
                        <label for="retryBackoffSchedule">Backoff (segundos, coma)</label>
                        <input id="retryBackoffSchedule" class="input" type="text" wire:model.live="retryBackoffSchedule" placeholder="30,60,300,900,3600">
                        @error('retryBackoffSchedule') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                    </div>
                    <div>
                        <label for="retryHttpTimeout">Timeout HTTP (segundos)</label>
                        <input id="retryHttpTimeout" class="input" type="number" min="5" max="60" wire:model.live="retryHttpTimeout">
                        @error('retryHttpTimeout') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <div style="margin-top: 1rem; display: flex; align-items: center; gap: 0.6rem;">
                <button type="submit" class="btn" data-tooltip="Guardar configuración de integraciones">
                    <span style="display:inline-flex; align-items:center; gap:.35rem;"><i data-lucide="save"></i>Guardar configuración</span>
                </button>
                <span class="muted" style="font-size: 0.85rem;">Se actualiza el archivo .env de este proyecto.</span>
            </div>
        </form>
    </section>

    <section class="card card-pad" style="margin-top: 1rem;">
        <h3 style="margin: 0 0 0.55rem;">Diagnostico de conectividad</h3>
        <div style="display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center; margin-bottom: 0.65rem;">
            <button class="btn" type="button" wire:click="testBotmakerConnection" wire:loading.attr="disabled" wire:target="testBotmakerConnection" data-tooltip="Probar credenciales contra API de Botmaker">
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><i data-lucide="plug-zap"></i>Probar conexión Botmaker</span>
            </button>
            <span class="muted" wire:loading wire:target="testBotmakerConnection">Probando conexión con Botmaker...</span>
        </div>
        @if ($botmakerTestMessage)
            <p style="margin: 0 0 0.8rem; color: {{ $botmakerTestOk ? '#166534' : '#b91c1c' }};">{{ $botmakerTestMessage }}</p>
        @endif

        <div style="display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center; margin-bottom: 0.65rem;">
            <button class="btn" type="button" wire:click="testBitrix24Connection" wire:loading.attr="disabled" wire:target="testBitrix24Connection" data-tooltip="Probar webhook y conexión con Bitrix24">
                <span style="display:inline-flex; align-items:center; gap:.35rem;"><i data-lucide="shield-check"></i>Probar conexión Bitrix24</span>
            </button>
            <span class="muted" wire:loading wire:target="testBitrix24Connection">Probando conexión con Bitrix24...</span>
        </div>
        @if ($bitrixTestMessage)
            <p style="margin: 0; color: {{ $bitrixTestOk ? '#166534' : '#b91c1c' }};">{{ $bitrixTestMessage }}</p>
        @endif
    </section>
</div>

<script>
    (function () {
        document.addEventListener('click', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;

            const button = target.closest('[data-toggle-password]');
            if (!button) return;

            const inputId = button.getAttribute('data-toggle-password');
            if (!inputId) return;

            const input = document.getElementById(inputId);
            if (!(input instanceof HTMLInputElement)) return;

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Ocultar' : 'Ver';
        });
    })();
</script>
