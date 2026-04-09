<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Centro de configuración</h2>
            <p class="page-subtitle">Configuración esencial del flujo Botmaker → WebService → Bitrix24.</p>
        </div>
    </div>

    <div class="grid gap-3" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
        @if($isAdmin)
        <a href="{{ url('/monitor/settings/botmaker') }}" class="card card-pad settings-hub-card" style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--app-border); transition: border-color .15s;">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <span class="settings-hub-icon" aria-hidden="true"><i data-lucide="message-circle"></i></span>
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <h3 style="margin: 0; font-size: 1.05rem;">Conexión Botmaker</h3>
                        @if($botmakerConfigured)<span class="badge-soft" style="border-color: #16a34a; color: #16a34a;">Configurado</span>@else<span class="badge-soft" style="border-color: #dc2626; color: #dc2626;">Requiere configuración</span>@endif
                    </div>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Configura URL API y token JWT para consultar datos de Botmaker.</p>
                    <p class="muted" style="margin:.35rem 0 0; font-size:.78rem;">Actualizado {{ $botmakerUpdatedAt }}</p>
                </div>
            </div>
        </a>
        <a href="{{ url('/monitor/settings/bitrix24') }}" class="card card-pad settings-hub-card" style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--app-border);">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <span class="settings-hub-icon" aria-hidden="true"><i data-lucide="contact"></i></span>
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <h3 style="margin: 0; font-size: 1.05rem;">Conexión Bitrix24</h3>
                        @if($bitrixConfigured)<span class="badge-soft" style="border-color: #16a34a; color: #16a34a;">OAuth OK</span>@else<span class="badge-soft" style="border-color: #dc2626; color: #dc2626;">Sin token OAuth</span>@endif
                    </div>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">OAuth v2, conector imconnector y Canal Abierto.</p>
                    @if($bitrixTokenDomain)<p class="muted" style="margin:.2rem 0 0; font-size:.78rem;">Dominio: {{ $bitrixTokenDomain }}</p>@endif
                    <p class="muted" style="margin:.35rem 0 0; font-size:.78rem;">Actualizado {{ $bitrixUpdatedAt }}</p>
                </div>
            </div>
        </a>
        <a href="{{ url('/monitor/settings/tokens') }}" class="card card-pad settings-hub-card" style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--app-border);">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <span class="settings-hub-icon" aria-hidden="true"><i data-lucide="shield"></i></span>
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <h3 style="margin: 0; font-size: 1.05rem;">Webhooks autorizados</h3>
                        <span class="badge-soft">{{ $activeTokensCount }} tokens activos</span>
                    </div>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Gestiona solo los tokens de webhook entrante de Botmaker.</p>
                </div>
            </div>
        </a>
        <a href="{{ url('/monitor/settings/retry') }}" class="card card-pad settings-hub-card" style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--app-border);">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <span class="settings-hub-icon" aria-hidden="true"><i data-lucide="timer"></i></span>
                <div style="min-width: 0; flex: 1;">
                    <h3 style="margin: 0 0 0.35rem; font-size: 1.05rem;">Reintentos y rendimiento</h3>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Intervalos de reintento, timeouts y cola de procesamiento.</p>
                </div>
            </div>
        </a>
        <a href="{{ url('/monitor/settings/test') }}" class="card card-pad settings-hub-card" style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--app-border);">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <span class="settings-hub-icon" aria-hidden="true"><i data-lucide="play-circle"></i></span>
                <div style="min-width: 0; flex: 1;">
                    <h3 style="margin: 0 0 0.35rem; font-size: 1.05rem;">Pruebas de integración</h3>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Verifica conectividad con Botmaker y Bitrix24.</p>
                </div>
            </div>
        </a>
        @endif

        @if(user_can('mappings.manage'))
        <a href="{{ url('/monitor/field-mappings') }}" class="card card-pad settings-hub-card" style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--app-border);">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <span class="settings-hub-icon" aria-hidden="true"><i data-lucide="git-compare-arrows"></i></span>
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <h3 style="margin: 0; font-size: 1.05rem;">Mapeo de campos</h3>
                        <span class="badge-soft" style="border-color: #eab308; color: #92400e;">Legacy v1</span>
                        <span class="badge-soft">{{ $fieldMappingsCount }} campos mapeados</span>
                    </div>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Mapeos de campos para crm.lead.add (v1). En v2, Bitrix24 gestiona leads desde el Canal Abierto.</p>
                </div>
            </div>
        </a>
        @endif
    </div>

    @if(!$isAdmin)
        <section class="card card-pad" style="margin-top: 1rem;">
            <p class="muted" style="margin: 0;">Las conexiones de plataforma, tokens, reintentos y pruebas de integración solo las configura un administrador.</p>
        </section>
    @endif
</div>

<style>
    .settings-hub-card:hover { border-color: var(--app-muted) !important; }
    .settings-hub-icon { display: inline-flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: var(--sidebar-row); flex-shrink: 0; }
    .settings-hub-icon svg { width: 1.25rem; height: 1.25rem; }
    @media (max-width: 768px) {
        .grid[style*="repeat(2"] { grid-template-columns: 1fr !important; }
    }
</style>
