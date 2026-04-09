<div class="integration-manual" id="manual-top">
    <style>
        .integration-manual .im-head { display:flex; flex-wrap:wrap; gap:.75rem; justify-content:space-between; margin-bottom:1rem; }
        .integration-manual .im-layout { display:grid; grid-template-columns:260px minmax(0,1fr); gap:1rem; }
        .integration-manual .im-nav { position:sticky; top:4rem; border:1px solid var(--app-border); border-radius:.65rem; background:var(--app-surface); padding:.75rem; height:fit-content; }
        .integration-manual .im-nav a { display:block; padding:.35rem .5rem; text-decoration:none; color:var(--app-muted); border-radius:.45rem; font-size:.85rem; }
        .integration-manual .im-nav a:hover { background:var(--app-row); color:var(--app-text); }
        .integration-manual .im-card { border:1px solid var(--app-border); border-radius:.7rem; background:var(--app-surface); padding:1rem; margin-bottom:1rem; }
        .integration-manual .im-card h2 { margin:0 0 .55rem; font-size:1.06rem; }
        .integration-manual .im-card p, .integration-manual .im-card li { font-size:.92rem; line-height:1.55; }
        .integration-manual .flow { font-weight:700; background:var(--app-row); border-radius:.55rem; padding:.75rem; font-size:.88rem; line-height:1.6; }
        .integration-manual .flow-label { display:inline-block; background:#e0e7ff; color:#3730a3; padding:.1rem .4rem; border-radius:.3rem; font-size:.78rem; font-weight:600; margin-right:.25rem; }
        .integration-manual .code-block { background:var(--app-row); border:1px solid var(--app-border); border-radius:.4rem; padding:.6rem .8rem; font-family:monospace; font-size:.82rem; overflow-x:auto; margin:.5rem 0; }
        .integration-manual .step-num { display:inline-flex; align-items:center; justify-content:center; width:1.4rem; height:1.4rem; border-radius:999px; background:#4f46e5; color:#fff; font-size:.72rem; font-weight:700; margin-right:.35rem; flex-shrink:0; }
        .integration-manual .warn-box { background:#fefce8; border:1px solid #facc15; border-radius:.5rem; padding:.6rem .8rem; font-size:.88rem; margin:.5rem 0; }
        @media (max-width: 980px) {
            .integration-manual .im-layout { grid-template-columns:1fr; }
            .integration-manual .im-nav { position:static; }
        }
    </style>

    <div class="im-head">
        <div>
            <h2 class="page-title" style="margin-bottom:.2rem;">Manual de integración v2</h2>
            <p class="page-subtitle">Flujo bidireccional: Botmaker (WhatsApp) ↔ WebService ↔ Bitrix24 (Canal Abierto / imconnector).</p>
        </div>
        <input class="input" type="text" wire:model.live="search" placeholder="Buscar en el manual..." style="max-width:260px;">
    </div>

    <div class="im-layout">
        <nav class="im-nav" aria-label="Navegación del manual">
            @foreach($filteredSections as $section)
                <a href="#{{ $section['id'] }}">{{ $section['title'] }}</a>
            @endforeach
        </nav>

        <main>
            {{-- 1. Arquitectura general --}}
            <section class="im-card" id="sec-1">
                <h2>1) Arquitectura general</h2>
                <p>El WebService actúa como puente bidireccional entre <strong>Botmaker</strong> (plataforma WhatsApp) y <strong>Bitrix24</strong> (CRM con Canal Abierto). Utiliza la API <code>imconnector.*</code> de Bitrix24 con autenticación OAuth 2.0.</p>
                <div class="flow">
                    <span class="flow-label">Flujo A</span> Cliente WhatsApp → Botmaker → WebService → imconnector.send.messages → Canal Abierto Bitrix24<br>
                    <span class="flow-label">Flujo B</span> Agente Bitrix24 → OnImConnectorMessageAdd → WebService → BotmakerAPI → WhatsApp Cliente
                </div>
                <ul>
                    <li><strong>Autenticación Bitrix24:</strong> OAuth 2.0 con refresh automático de tokens (App Local).</li>
                    <li><strong>Autenticación Botmaker:</strong> Header <code>auth-bm-token</code> para webhooks entrantes, header <code>access-token</code> (JWT) para llamadas a la API.</li>
                    <li><strong>Procesamiento:</strong> Asíncrono mediante colas Laravel (<code>webhooks</code>). Reintentos automáticos con backoff exponencial.</li>
                    <li><strong>Deduplicación:</strong> Cache locks para evitar mensajes duplicados en ambos flujos.</li>
                    <li><strong>Anti-loop:</strong> Filtrado de mensajes de sistema y mensajes auto-inyectados para evitar bucles infinitos.</li>
                </ul>
            </section>

            {{-- 2. Flujo A --}}
            <section class="im-card" id="sec-2">
                <h2>2) Flujo A: Cliente escribe por WhatsApp → Agente en Bitrix24</h2>
                <ol>
                    <li><span class="step-num">1</span>Cliente envía mensaje por WhatsApp.</li>
                    <li><span class="step-num">2</span>Botmaker recibe el mensaje y dispara webhook hacia <code>POST /api/webhook/botmaker</code>.</li>
                    <li><span class="step-num">3</span>El middleware valida el header <code>auth-bm-token</code> contra los tokens activos en la base de datos.</li>
                    <li><span class="step-num">4</span>Se registra el webhook en <code>webhook_logs</code> y se despacha el job <code>ProcessBotmakerPayload</code> a la cola.</li>
                    <li><span class="step-num">5</span>El job extrae: teléfono (<code>contactId</code>/<code>whatsappNumber</code>), nombre (<code>firstName</code>/<code>lastName</code>) y texto del mensaje.</li>
                    <li><span class="step-num">6</span>Deduplicación: si el <code>messageId</code> ya fue procesado (cache lock 60s), se descarta.</li>
                    <li><span class="step-num">7</span>Llama a <code>Bitrix24ConnectorService::sendSingleMessage()</code> que ejecuta <code>imconnector.send.messages</code> con el token OAuth.</li>
                    <li><span class="step-num">8</span>El mensaje aparece en el chat del Canal Abierto de Bitrix24. Bitrix24 automáticamente crea lead/contacto y asigna agente.</li>
                    <li><span class="step-num">9</span>El agente ve el mensaje en su bandeja y puede responder (Flujo B).</li>
                </ol>
                <div class="warn-box">Si falla el envío a Bitrix24, el job reintenta hasta 5 veces con backoff. Si se agotan los reintentos, queda registrado en <code>failed_webhooks</code> para reintento manual.</div>
            </section>

            {{-- 3. Flujo B --}}
            <section class="im-card" id="sec-3">
                <h2>3) Flujo B: Agente responde desde Bitrix24 → Cliente recibe en WhatsApp</h2>
                <ol>
                    <li><span class="step-num">1</span>El agente escribe en el chat del Canal Abierto de Bitrix24.</li>
                    <li><span class="step-num">2</span>Bitrix24 dispara el evento <code>OnImConnectorMessageAdd</code> al handler de la App Local.</li>
                    <li><span class="step-num">3</span>El WebService recibe el evento en <code>POST /api/bitrix24/handler</code>.</li>
                    <li><span class="step-num">4</span>Se valida el <code>application_token</code> contra el token almacenado en <code>bitrix24_tokens</code>.</li>
                    <li><span class="step-num">5</span>Filtros de seguridad: se ignoran mensajes de sistema, mensajes de otros conectores, mensajes auto-inyectados (anti-loop) y mensajes ya procesados (idempotencia).</li>
                    <li><span class="step-num">6</span>Se extrae: texto del agente, <code>chat_id</code> (que corresponde al teléfono del cliente) y se despacha <code>SendBotmakerMessage</code>.</li>
                    <li><span class="step-num">7</span>El job llama a <code>BotmakerService::sendMessage()</code> que envía <code>POST</code> a la API de Botmaker con el JWT token.</li>
                    <li><span class="step-num">8</span>Botmaker entrega el mensaje por WhatsApp al cliente.</li>
                    <li><span class="step-num">9</span>El WebService confirma la entrega a Bitrix24 vía <code>imconnector.send.status.delivery</code>.</li>
                </ol>
                <div class="warn-box">La confirmación de entrega (paso 9) es no bloqueante: si falla, el mensaje ya fue enviado al cliente. Solo afecta el indicador de "entregado" en Bitrix24.</div>
            </section>

            {{-- 4. Requisitos previos --}}
            <section class="im-card" id="sec-4">
                <h2>4) Requisitos previos</h2>
                <table class="table-clean" style="min-width:0;">
                    <thead><tr><th>Requisito</th><th>Detalle</th></tr></thead>
                    <tbody>
                        <tr><td>PHP</td><td>8.2 o superior</td></tr>
                        <tr><td>MySQL / MariaDB</td><td>5.7+ / 10.3+</td></tr>
                        <tr><td>SSL/HTTPS</td><td>Obligatorio. Tanto Botmaker como Bitrix24 requieren HTTPS.</td></tr>
                        <tr><td>Dominio</td><td>Dominio apuntando al servidor con certificado SSL válido.</td></tr>
                        <tr><td>Queue Worker</td><td>Proceso permanente ejecutando <code>php artisan queue:work --queue=webhooks</code>. Usar Supervisor o systemd.</td></tr>
                        <tr><td>APP_KEY</td><td>Generado y <strong>nunca cambiado</strong> después del deploy inicial. Los tokens OAuth se encriptan con esta clave.</td></tr>
                        <tr><td>Bitrix24</td><td>Plan con soporte para Canal Abierto y Apps Locales (Developer resources).</td></tr>
                        <tr><td>Botmaker</td><td>Cuenta con acceso a webhooks de salida y API REST.</td></tr>
                    </tbody>
                </table>
            </section>

            {{-- 5. Configuración desde el panel --}}
            <section class="im-card" id="sec-5">
                <h2>5) Configuración desde el panel de administración</h2>
                <p>Toda la configuración se realiza desde el panel web. <strong>No es necesario editar el archivo <code>.env</code> en producción.</strong></p>

                <h3 style="margin:1rem 0 .4rem; font-size:.98rem;">5.1 Conexión Bitrix24 (<code>/monitor/settings/bitrix24</code>)</h3>
                <ol>
                    <li>Ingresar el <strong>dominio</strong> de Bitrix24 (sin <code>https://</code>). Ejemplo: <code>miempresa.bitrix24.mx</code></li>
                    <li>Ingresar el <strong>Client ID</strong> y <strong>Client Secret</strong> de la App Local (se obtienen en Bitrix24 > Developer resources).</li>
                    <li>Ingresar el <strong>Connector ID</strong> (default: <code>botmaker_whatsapp</code>) y el <strong>Line ID</strong> del Canal Abierto.</li>
                    <li>Click en <strong>"Guardar configuración OAuth"</strong>.</li>
                    <li>Click en <strong>"Registrar / Activar conector"</strong> para registrar el conector en Bitrix24.</li>
                    <li>Verificar con <strong>"Test OAuth Token"</strong> y <strong>"Test imconnector.status"</strong>.</li>
                </ol>

                <h3 style="margin:1rem 0 .4rem; font-size:.98rem;">5.2 Conexión Botmaker (<code>/monitor/settings/botmaker</code>)</h3>
                <ol>
                    <li>En la sección <strong>"Token de API Botmaker"</strong>, ingresar:
                        <ul>
                            <li><strong>URL base de API:</strong> normalmente <code>https://go.botmaker.com/api/v1.0</code></li>
                            <li><strong>Token de API (access-token):</strong> el JWT que Botmaker proporciona para su API REST.</li>
                            <li><strong>Endpoint de envío:</strong> normalmente <code>/message/v2</code></li>
                        </ul>
                    </li>
                    <li>Click en <strong>"Guardar configuración API"</strong>.</li>
                    <li>Click en <strong>"Probar conexión API"</strong> para verificar que el token sea válido.</li>
                    <li>Copiar la <strong>URL del webhook</strong> que se muestra en la sección de recepción.</li>
                </ol>

                <h3 style="margin:1rem 0 .4rem; font-size:.98rem;">5.3 Webhooks autorizados (<code>/monitor/settings/tokens</code>)</h3>
                <ol>
                    <li>Crear un token de plataforma <code>botmaker</code>, dirección <code>outgoing</code>.</li>
                    <li>El valor del token debe coincidir <strong>exactamente</strong> con el <code>auth-bm-token</code> configurado en Botmaker.</li>
                    <li>Asegurarse de que el token esté marcado como <strong>Activo</strong>.</li>
                </ol>
            </section>

            {{-- 6. Configuración en Bitrix24 --}}
            <section class="im-card" id="sec-6">
                <h2>6) Configuración en Bitrix24</h2>
                <h3 style="margin:.75rem 0 .4rem; font-size:.98rem;">6.1 Crear la App Local</h3>
                <ol>
                    <li>Ir a <strong>Aplicaciones > Developer resources > Otra > App Local</strong>.</li>
                    <li>Configurar:
                        <ul>
                            <li><strong>Handler path:</strong> <code>https://tu-dominio.com/api/bitrix24/handler</code></li>
                            <li><strong>Initial install path:</strong> <code>https://tu-dominio.com/api/bitrix24/install</code></li>
                        </ul>
                    </li>
                    <li>Permisos requeridos: <code>imconnector</code>, <code>imopenlines</code>, <code>crm</code>.</li>
                    <li>Guardar. Bitrix24 proporcionará el <strong>Client ID</strong> y <strong>Client Secret</strong>.</li>
                    <li><strong>Instalar</strong> la App Local. Esto dispara una petición a <code>/api/bitrix24/install</code> que almacena los tokens OAuth automáticamente.</li>
                </ol>

                <h3 style="margin:.75rem 0 .4rem; font-size:.98rem;">6.2 Crear Canal Abierto</h3>
                <ol>
                    <li>Ir a <strong>Contact Center > Canal Abierto</strong>.</li>
                    <li>Crear un nuevo canal o usar uno existente.</li>
                    <li>Anotar el <strong>ID de la línea</strong> (visible en la URL o configuración del canal).</li>
                    <li>Desde el panel del WebService, ejecutar <strong>"Registrar / Activar conector"</strong>.</li>
                    <li>En Bitrix24, verificar que el conector <strong>Botmaker WhatsApp</strong> aparezca en Contact Center.</li>
                </ol>
                <div class="warn-box">Después de instalar la App Local, verificar en el panel del WebService que el estado del token OAuth muestre "Válido". Si muestra "Expirado", el refresh automático se activará en la próxima petición.</div>
            </section>

            {{-- 7. Configuración en Botmaker --}}
            <section class="im-card" id="sec-7">
                <h2>7) Configuración en Botmaker</h2>
                <ol>
                    <li>Ir al panel de Botmaker > <strong>Webhooks de salida</strong>.</li>
                    <li>Configurar la URL del webhook: <code>https://tu-dominio.com/api/webhook/botmaker</code></li>
                    <li>Configurar el token de seguridad (<code>auth-bm-token</code>): debe coincidir con el token activo en <strong>Webhooks autorizados</strong> del panel.</li>
                    <li>Guardar en Botmaker.</li>
                    <li>Obtener el <strong>API Token (JWT)</strong> desde Botmaker > Configuración > API/Integraciones.</li>
                    <li>Ingresar ese token en el panel del WebService > <strong>Conexión Botmaker > Token de API</strong>.</li>
                </ol>
            </section>

            {{-- 8. Validación diaria --}}
            <section class="im-card" id="sec-8">
                <h2>8) Validación diaria</h2>
                <p>Revisar desde el <strong>Tablero</strong> (<code>/monitor</code>):</p>
                <ul>
                    <li>Hay actividad en webhooks recibidos hoy (dirección Botmaker → Bitrix y Bitrix → Botmaker).</li>
                    <li>Webhooks exitosos &gt; fallidos.</li>
                    <li>Último webhook dentro de la ventana operativa esperada.</li>
                    <li>No hay acumulación de reintentos pendientes en <strong>Webhooks fallidos</strong>.</li>
                    <li>En <strong>Conexión Bitrix24</strong>: token OAuth muestra estado "Válido".</li>
                    <li>El widget <strong>Connector Health</strong> en el dashboard muestra verde para OAuth, imconnector y Botmaker.</li>
                </ul>
            </section>

            {{-- 9. Errores comunes --}}
            <section class="im-card" id="sec-9">
                <h2>9) Errores comunes y solución</h2>
                <table class="table-clean" style="min-width:0;">
                    <thead>
                        <tr><th>Error</th><th>Causa</th><th>Solución</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>401</strong> en webhook Botmaker</td>
                            <td>Token <code>auth-bm-token</code> no coincide</td>
                            <td>Sincronizar token entre Botmaker y panel > Webhooks autorizados</td>
                        </tr>
                        <tr>
                            <td><strong>422</strong> en webhook</td>
                            <td>Payload sin teléfono (<code>contactId</code>/<code>whatsappNumber</code>)</td>
                            <td>Verificar formato del webhook en Botmaker</td>
                        </tr>
                        <tr>
                            <td><strong>401</strong> en handler Bitrix24</td>
                            <td><code>application_token</code> inválido</td>
                            <td>Reinstalar la App Local en Bitrix24</td>
                        </tr>
                        <tr>
                            <td><strong>OAuth token expired</strong></td>
                            <td>El refresh automático falló (client_id/secret cambiaron)</td>
                            <td>Verificar credenciales en panel > Bitrix24, reinstalar App Local si es necesario</td>
                        </tr>
                        <tr>
                            <td><strong>Botmaker API Token inválido</strong></td>
                            <td>JWT expirado o incorrecto</td>
                            <td>Obtener nuevo token en Botmaker y actualizarlo en panel > Conexión Botmaker</td>
                        </tr>
                        <tr>
                            <td><strong>Mensajes no llegan</strong> (sin error)</td>
                            <td>Queue worker detenido</td>
                            <td>Verificar que <code>php artisan queue:work</code> esté corriendo (Supervisor/systemd)</td>
                        </tr>
                        <tr>
                            <td><strong>500</strong> interno</td>
                            <td>Error de código o DB</td>
                            <td>Revisar <code>storage/logs/laravel.log</code> y <code>webhook</code> channel log</td>
                        </tr>
                        <tr>
                            <td><strong>Conector no aparece</strong></td>
                            <td>No se ejecutó el setup</td>
                            <td>Click en "Registrar / Activar conector" en panel > Bitrix24</td>
                        </tr>
                        <tr>
                            <td><strong>Mensajes duplicados</strong></td>
                            <td>Cache driver no funcional</td>
                            <td>Verificar <code>CACHE_STORE</code> en .env y que la tabla <code>cache</code> exista</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            {{-- 10. Comandos Artisan --}}
            <section class="im-card" id="sec-10">
                <h2>10) Comandos Artisan útiles</h2>
                <table class="table-clean" style="min-width:0;">
                    <thead><tr><th>Comando</th><th>Descripción</th></tr></thead>
                    <tbody>
                        <tr><td><code>php artisan queue:work --queue=webhooks</code></td><td>Inicia el worker de colas (obligatorio en producción).</td></tr>
                        <tr><td><code>php artisan bitrix24:setup-connector</code></td><td>Registra, activa y configura el conector en Bitrix24 (CLI).</td></tr>
                        <tr><td><code>php artisan migrate --force</code></td><td>Ejecuta migraciones pendientes en producción.</td></tr>
                        <tr><td><code>php artisan db:seed</code></td><td>Crea el usuario admin inicial.</td></tr>
                        <tr><td><code>php artisan config:clear</code></td><td>Limpia la cache de configuración.</td></tr>
                        <tr><td><code>php artisan route:clear</code></td><td>Limpia la cache de rutas.</td></tr>
                        <tr><td><code>php artisan manual:pdf</code></td><td>Genera el PDF del manual en <code>public/docs/</code>.</td></tr>
                    </tbody>
                </table>
            </section>

            {{-- 11. Responsables por área --}}
            <section class="im-card" id="sec-11">
                <h2>11) Responsables por área</h2>
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

            {{-- Endpoints reference --}}
            <section class="im-card">
                <h2>Referencia de endpoints</h2>
                <table class="table-clean" style="min-width:0;">
                    <thead><tr><th>Método</th><th>Ruta</th><th>Descripción</th></tr></thead>
                    <tbody>
                        <tr><td><code>POST</code></td><td><code>/api/webhook/botmaker</code></td><td>Recibe webhooks de Botmaker (Flujo A entrada)</td></tr>
                        <tr><td><code>GET|POST</code></td><td><code>/api/bitrix24/install</code></td><td>Recibe tokens OAuth al instalar la App Local</td></tr>
                        <tr><td><code>POST</code></td><td><code>/api/bitrix24/handler</code></td><td>Recibe eventos de Bitrix24 (Flujo B entrada)</td></tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
