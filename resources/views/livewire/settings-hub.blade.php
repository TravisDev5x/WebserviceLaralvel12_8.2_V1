@php($isAdmin = auth()->check() && (string) (auth()->user()->role ?? '') === 'admin')
<div>
    <div class="page-header">
        <div>
            <h2 class="page-title">Centro de configuración</h2>
            <p class="page-subtitle">Elige un módulo para conectar integraciones, webhooks y automatización. Cada tarjeta indica si ya hay datos guardados.</p>
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
                        @if($botmakerConfigured)<span class="badge-soft" style="border-color: #16a34a; color: #16a34a;">Conectado</span>@else<span class="badge-soft" style="border-color: #dc2626; color: #dc2626;">Sin configurar</span>@endif
                    </div>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Configura la conexión con la API de Botmaker para enviar y recibir mensajes de WhatsApp.</p>
                </div>
            </div>
        </a>
        <a href="{{ url('/monitor/settings/bitrix24') }}" class="card card-pad settings-hub-card" style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--app-border);">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <span class="settings-hub-icon" aria-hidden="true"><i data-lucide="contact"></i></span>
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <h3 style="margin: 0; font-size: 1.05rem;">Conexión Bitrix24</h3>
                        @if($bitrixConfigured)<span class="badge-soft" style="border-color: #16a34a; color: #16a34a;">Conectado</span>@else<span class="badge-soft" style="border-color: #dc2626; color: #dc2626;">Sin configurar</span>@endif
                    </div>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Configura la conexión con Bitrix24 CRM para crear y actualizar leads.</p>
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
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Gestiona los tokens de autenticación para recibir webhooks de cada plataforma.</p>
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
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Verifica conexiones con Botmaker y Bitrix24 y simula flujos de webhook.</p>
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
                        <span class="badge-soft">{{ $fieldMappingsCount }} campos mapeados</span>
                    </div>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Define qué campos de Botmaker se mapean a campos de Bitrix24.</p>
                </div>
            </div>
        </a>
        @endif
        @if(user_can('notifications.manage'))
        <a href="{{ url('/monitor/notification-rules') }}" class="card card-pad settings-hub-card" style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--app-border);">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <span class="settings-hub-icon" aria-hidden="true"><i data-lucide="bell-ring"></i></span>
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <h3 style="margin: 0; font-size: 1.05rem;">Reglas de notificación</h3>
                        <span class="badge-soft">{{ $notificationRulesActive }} reglas activas</span>
                    </div>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Qué eventos del CRM envían mensaje al usuario por WhatsApp.</p>
                </div>
            </div>
        </a>
        @endif
        @if(user_can('templates.manage'))
        <a href="{{ url('/monitor/templates') }}" class="card card-pad settings-hub-card" style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--app-border);">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <span class="settings-hub-icon" aria-hidden="true"><i data-lucide="file-text"></i></span>
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <h3 style="margin: 0; font-size: 1.05rem;">Plantillas de mensajes</h3>
                        <span class="badge-soft">{{ $templatesCount }} plantillas</span>
                    </div>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Mensajes predefinidos enviados por WhatsApp según el evento.</p>
                </div>
            </div>
        </a>
        @endif
        @if(user_can('whatsapp.manage'))
        <a href="{{ url('/monitor/whatsapp-numbers') }}" class="card card-pad settings-hub-card" style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--app-border);">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <span class="settings-hub-icon" aria-hidden="true"><i data-lucide="phone"></i></span>
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <h3 style="margin: 0; font-size: 1.05rem;">Números WhatsApp</h3>
                        <span class="badge-soft">{{ $whatsappActive }} números activos</span>
                    </div>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Números de WhatsApp Business para envío de mensajes.</p>
                </div>
            </div>
        </a>
        @endif
        @if(user_can('alerts.manage'))
        <a href="{{ url('/monitor/alerts') }}" class="card card-pad settings-hub-card" style="text-decoration: none; color: inherit; display: block; border: 1px solid var(--app-border);">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <span class="settings-hub-icon" aria-hidden="true"><i data-lucide="alert-triangle"></i></span>
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <h3 style="margin: 0; font-size: 1.05rem;">Alertas</h3>
                        <span class="badge-soft">{{ $alertsActive }} alertas activas</span>
                    </div>
                    <p class="muted" style="margin: 0; font-size: 0.88rem; line-height: 1.45;">Notificaciones por correo cuando algo falla.</p>
                </div>
            </div>
        </a>
        @endif
    </div>

    @if(!$isAdmin)
        <section class="card card-pad" style="margin-top: 1rem;">
            <p class="muted" style="margin: 0;">Las conexiones Botmaker, Bitrix24, webhooks autorizados, reintentos y pruebas de integración solo las configura un administrador. Usa el menú lateral para mapeos, reglas y plantillas si tienes permiso.</p>
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
