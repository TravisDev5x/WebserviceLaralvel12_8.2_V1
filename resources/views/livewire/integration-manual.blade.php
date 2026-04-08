<div class="integration-manual" id="manual-top">
    <style>
        .integration-manual .im-head { display:flex; flex-wrap:wrap; gap:.75rem; justify-content:space-between; margin-bottom:1rem; }
        .integration-manual .im-layout { display:grid; grid-template-columns:260px minmax(0,1fr); gap:1rem; }
        .integration-manual .im-nav { position:sticky; top:4rem; border:1px solid var(--app-border); border-radius:.65rem; background:var(--app-surface); padding:.75rem; height:fit-content; }
        .integration-manual .im-nav a { display:block; padding:.35rem .5rem; text-decoration:none; color:var(--app-muted); border-radius:.45rem; font-size:.85rem; }
        .integration-manual .im-nav a:hover { background:var(--app-row); color:var(--app-text); }
        .integration-manual .im-card { border:1px solid var(--app-border); border-radius:.7rem; background:var(--app-surface); padding:1rem; margin-bottom:1rem; }
        .integration-manual .im-card h2 { margin:0 0 .55rem; font-size:1.06rem; }
        .integration-manual .im-card p, .integration-manual .im-card li { font-size:.92rem; line-height:1.5; }
        .integration-manual .flow { font-weight:700; background:var(--app-row); border-radius:.55rem; padding:.75rem; }
        @media (max-width: 980px) {
            .integration-manual .im-layout { grid-template-columns:1fr; }
            .integration-manual .im-nav { position:static; }
        }
    </style>

    <div class="im-head">
        <div>
            <h2 class="page-title" style="margin-bottom:.2rem;">Manual de integración (versión simplificada)</h2>
            <p class="page-subtitle">Solo el flujo operativo actual: Botmaker envía webhooks y el sistema crea/actualiza leads en Bitrix24.</p>
        </div>
        <input class="input" type="text" wire:model.live="search" placeholder="Buscar en el manual...">
    </div>

    <div class="im-layout">
        <nav class="im-nav" aria-label="Navegación del manual">
            @foreach($filteredSections as $section)
                <a href="#{{ $section['id'] }}">{{ $section['title'] }}</a>
            @endforeach
        </nav>

        <main>
            <section class="im-card" id="sec-1">
                <h2>1) Flujo del sistema</h2>
                <p class="flow">Cliente WhatsApp -> Botmaker -> /api/webhook/botmaker -> Cola webhooks -> Bitrix24 (crm.lead.add / crm.lead.update)</p>
                <ul>
                    <li>El webservice recibe eventos de Botmaker y valida `auth-bm-token`.</li>
                    <li>Se registra cada intento en `webhook_logs`.</li>
                    <li>El procesamiento es asíncrono con `ProcessBotmakerPayload`.</li>
                    <li>Si falla, queda registro en `failed_webhooks` para reintento.</li>
                </ul>
            </section>

            <section class="im-card" id="sec-2">
                <h2>2) Configuración mínima</h2>
                <ol>
                    <li>Definir `APP_URL` con HTTPS.</li>
                    <li>Configurar en Botmaker la URL: `APP_URL/api/webhook/botmaker`.</li>
                    <li>Configurar el token de seguridad en Botmaker y en `authorized_tokens` (plataforma `botmaker`).</li>
                    <li>Verificar en Monitor que existan webhooks recibidos y exitosos.</li>
                </ol>
                <p><strong>Nota:</strong> este sistema no envía mensajes a Botmaker.</p>
            </section>

            <section class="im-card" id="sec-3">
                <h2>3) Validación diaria</h2>
                <ul>
                    <li>Hay actividad en webhooks recibidos hoy.</li>
                    <li>Exitosos &gt; fallidos en el día.</li>
                    <li>Último webhook dentro de la ventana operativa esperada.</li>
                    <li>No hay acumulación de reintentos pendientes.</li>
                </ul>
            </section>

            <section class="im-card" id="sec-4">
                <h2>4) Errores comunes y solución</h2>
                <table class="table-clean" style="min-width:0;">
                    <thead>
                        <tr><th>Error</th><th>Causa típica</th><th>Acción recomendada</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>401</td><td>Token inválido o ausente</td><td>Validar `auth-bm-token` y token activo en `authorized_tokens`</td></tr>
                        <tr><td>422</td><td>Payload sin `contactId` o incompleto</td><td>Corregir estructura enviada por Botmaker</td></tr>
                        <tr><td>500</td><td>Error interno/servicios</td><td>Revisar `laravel.log`, cola `webhooks` y conectividad a Bitrix24</td></tr>
                        <tr><td>No se crea lead</td><td>Error de webhook Bitrix24 o mapeo</td><td>Validar URL/permiso del webhook y campos requeridos</td></tr>
                    </tbody>
                </table>
            </section>

            <section class="im-card" id="sec-5">
                <h2>5) Responsables por área</h2>
                <table class="table-clean" style="min-width:0;">
                    <thead>
                        <tr><th>Situación</th><th>Área responsable</th><th>Acción</th></tr>
                    </thead>
                    <tbody>
                        @foreach($responsibilities as $row)
                            <tr>
                                <td>{{ $row['situation'] }}</td>
                                <td>{{ $row['owner'] }}</td>
                                <td>{{ $row['action'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
