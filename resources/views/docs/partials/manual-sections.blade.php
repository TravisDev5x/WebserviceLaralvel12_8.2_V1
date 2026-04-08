{{-- Contenido único del manual (web pública, panel y PDF) --}}
<section class="manual-block" id="introduccion">
    <h2>1. ¿Qué es esta aplicación?</h2>
    <p>Es un <strong>middleware</strong> (puente) entre <strong>Botmaker</strong> (WhatsApp / bots conversacionales) y <strong>Bitrix24</strong> (CRM y leads). Recibe eventos por <strong>webhooks</strong>, los registra, los procesa y crea leads en Bitrix24.</p>
    <ul>
        <li><strong>Botmaker → Bitrix24:</strong> crea o actualiza <strong>leads</strong> en el CRM con los datos que llegan del bot.</li>
        <li><strong>Flujo activo:</strong> el sistema está enfocado en Botmaker → Bitrix24.</li>
    </ul>
</section>

<section class="manual-block" id="bitrix-requisitos">
    <h2>2. Bitrix24 — qué debes configurar (explícito)</h2>

    <h3>2.1 Webhook REST hacia Bitrix24</h3>
    <p>Para crear/actualizar leads desde el flujo Botmaker→Bitrix, necesitas una URL de webhook de Bitrix24 con permisos de CRM, por ejemplo:</p>
    <p><code>https://TU_PORTAL.bitrix24.com/rest/USUARIO/CODIGO_SECRETO/</code></p>
    <p>Esa URL completa va en <code>BITRIX24_WEBHOOK_URL</code>. La aplicación llamará <code>crm.lead.add</code> mediante POST a <code>{WEBHOOK_URL}/{metodo}</code>.</p>
    <p>Asegúrate de que el webhook tenga permisos para: <strong>crear leads</strong>.</p>
</section>

<section class="manual-block" id="botmaker-requisitos">
    <h2>3. Botmaker — qué debes configurar (explícito)</h2>

    <h3>3.1 Webhook de entrada hacia esta aplicación</h3>
    <p>Botmaker debe enviar eventos <strong>POST</strong> a:</p>
    <p><code>{{ $appBaseUrl }}/webhook/botmaker</code></p>

    <h3>3.2 Seguridad: cabecera de firma (obligatorio)</h3>
    <p>Cada petición debe incluir la cabecera HTTP:</p>
    <p><code>X-Botmaker-Signature: &lt;valor_secreto&gt;</code></p>
    <p>Ese valor debe ser <strong>idéntico</strong> (comparación segura) a <code>BOTMAKER_WEBHOOK_SECRET</code> en tu servidor. Si falta o no coincide → <strong>401 Invalid signature</strong>.</p>

    <h3>3.3 Formato mínimo del JSON que valida la aplicación</h3>
    <ul>
        <li><code>event</code> o <code>type</code>: cadena no vacía que identifique el evento.</li>
        <li>Teléfono o contacto: al menos uno de <code>whatsappNumber</code>, <code>contact.phone</code>, <code>phone</code>, <code>contactId</code> o <code>customerId</code> (como cadena identificable).</li>
    </ul>
    <p>La aplicación fusiona datos de <code>messages</code>, <code>clientPayload</code>, <code>context</code>, <code>attributes</code> y <code>variables</code> para obtener nombre, apellidos, fechas, etc. Conviene que el flujo del bot rellene esos datos de forma coherente con tus <strong>mapeos</strong>.</p>

    <h3>3.4 API de salida (opcional)</h3>
    <p>Debes disponer de:</p>
    <ul>
        <li><code>BOTMAKER_API_URL</code>: por defecto <code>https://go.botmaker.com/api/v1.0</code></li>
        <li><code>BOTMAKER_API_TOKEN</code>: token Bearer válido con permiso para enviar mensajes.</li>
    </ul>
    <p>La aplicación puede llamar al endpoint (POST):</p>
    <p><code>{BOTMAKER_API_URL}/chats-actions/send-messages</code></p>
    <p>con cuerpo JSON que incluye <code>chatPlatform: whatsapp</code>, <code>whatsappNumber</code> normalizado y el texto del mensaje. Si en el panel defines un <strong>número/canal por defecto</strong> (WhatsApp), se envía también <code>chatChannelId</code> cuando corresponde.</p>

    <h3>3.5 Correo electrónico (E-mail) hacia Bitrix</h3>
    <p>El flujo Botmaker→Bitrix puede enviar el <strong>EMAIL</strong> estándar del lead en Bitrix (mismo formato multivalor que <code>PHONE</code>: <code>VALUE</code> + <code>VALUE_TYPE: WORK</code>). Los alias de origen incluyen <code>email</code>, <code>correo</code>, <code>mail</code>, etc. (configurable en <code>config/integrations.php</code> o JSON en <code>.env</code>). El valor debe ser un correo válido; si no lo es, el campo no se envía.</p>
    <p>Si en tu portal el correo obligatorio es un <strong>campo personalizado</strong> (<code>UF_CRM_*</code>), define en el mapeo <code>email</code> → ese código (y comprueba en Bitrix si el campo admite formato múltiple o solo texto plano).</p>
</section>

<section class="manual-block" id="variables-entorno">
    <h2>4. Variables de entorno (.env) — referencia rápida</h2>
    <div class="table-wrap">
        <table class="table-clean doc-table">
            <thead>
                <tr><th>Variable</th><th>Uso</th></tr>
            </thead>
            <tbody>
                <tr><td><code>APP_URL</code></td><td>URL base pública del middleware (webhooks y enlaces).</td></tr>
                <tr><td><code>BOTMAKER_API_URL</code></td><td>Base de la API REST de Botmaker.</td></tr>
                <tr><td><code>BOTMAKER_API_TOKEN</code></td><td>Token para enviar mensajes y llamadas salientes.</td></tr>
                <tr><td><code>BOTMAKER_WEBHOOK_SECRET</code></td><td>Debe coincidir con <code>X-Botmaker-Signature</code>.</td></tr>
                <tr><td><code>BITRIX24_WEBHOOK_URL</code></td><td>URL REST de entrada (crm.lead.*, crm.contact.*).</td></tr>
                <tr><td><code>BITRIX24_WEBHOOK_SECRET</code></td><td>Debe coincidir con <code>auth.application_token</code>.</td></tr>
                <tr><td><code>BOTMAKER_SALARY_CURRENCY</code></td><td>Moneda para normalizar salario (ej. MXN).</td></tr>
                <tr><td><code>BOTMAKER_SOURCE_ALIASES_JSON</code></td><td>Opcional: JSON de alias de campos (ver <code>config/integrations.php</code>).</td></tr>
                <tr><td><code>BOTMAKER_BITRIX_FIELDS_JSON</code></td><td>Opcional: mapeo campo lógico → código de campo Bitrix.</td></tr>
                <tr><td><code>BOTMAKER_ENUM_MAPS_JSON</code></td><td>Opcional: mapas de listas (estado, semanas cotizadas, etc.).</td></tr>
            </tbody>
        </table>
    </div>
    <p>Valores sensibles también pueden gestionarse desde el panel en <strong>Configuración</strong> (tabla <code>settings</code>), con prioridad sobre el fichero de configuración cuando existan.</p>
</section>

<section class="manual-block" id="flujos">
    <h2>5. Flujos paso a paso</h2>

    <h3>5.1 Botmaker → Bitrix24</h3>
    <ol>
        <li>Llega POST a <code>/webhook/botmaker</code> con firma válida.</li>
        <li>Se crea un registro en <em>Registros de Webhooks</em> con <code>correlation_id</code>.</li>
        <li>Se valida firma/token de Botmaker y se registra en <em>Registros de Webhooks</em>.</li>
        <li>Se mapean los campos del payload (nombre, teléfono, correo, estado + mapeos dinámicos activos).</li>
        <li>Se envía a Bitrix24 por <code>crm.lead.add</code>.</li>
        <li>El resultado (éxito o error) queda en el mismo registro de webhook.</li>
    </ol>
</section>

<section class="manual-block" id="panel">
    <h2>6. Panel web (/monitor)</h2>
    <p>Tras iniciar sesión y verificar correo, según tu rol verás secciones como:</p>
    <ul>
        <li><strong>Tablero</strong> — resumen del día y últimos eventos.</li>
        <li><strong>Registros de Webhooks</strong> — auditoría y detalle por <code>correlation_id</code>.</li>
        <li><strong>Webhooks fallidos</strong> — reintentos y diagnóstico.</li>
        <li><strong>Configuración</strong> — credenciales y parámetros persistidos.</li>
        <li><strong>Mapeo de campos</strong> — rutas JSON → campos destino por plataforma.</li>
        <li><strong>Reglas de notificación</strong> — disponibles en panel (opcional, no forman parte del flujo principal actual).</li>
        <li><strong>Plantillas</strong> — textos reutilizables con variables.</li>
        <li><strong>Números WhatsApp</strong> — canal por defecto para envíos.</li>
        <li><strong>Filtros de eventos</strong> — ignorar o aceptar eventos según reglas.</li>
        <li><strong>Pruebas de integración</strong> — (permiso <code>settings.manage</code>, misma categoría que Configuración) permite ejecutar desde el navegador: creación de un <strong>lead de prueba completo</strong> en Bitrix24 y comprobación de conectividad (API Botmaker, API Bitrix vía <code>crm.lead.list</code>, y revisión de registros de webhook atorados en cola). Muestra resumen de webhooks del día. Ruta: <code>/monitor/integration-tests</code>.</li>
    </ul>
    <p>En la pantalla de <strong>inicio de sesión</strong> hay un enlace al manual público <code>/manual</code>.</p>
    <p>Esta misma guía está en <strong>Manual de integración</strong> del menú lateral (usuarios con acceso al tablero). La copia pública sin sesión es la ruta <code>/manual</code>; dentro de la app autenticada, <code>/monitor/manual</code>.</p>
</section>

<section class="manual-block" id="limites">
    <h2>7. Límites y buenas prácticas</h2>
    <ul>
        <li><strong>Rate limit:</strong> hasta 120 solicitudes por minuto por IP en rutas <code>/webhook/*</code>.</li>
        <li><strong>Reintentos:</strong> los jobs usan backoff configurable (<code>retry.*</code> en settings o config).</li>
        <li>No publiques tokens en repositorios; usa <code>.env</code> o el panel solo en servidores controlados.</li>
        <li>Prueba primero con eventos de prueba y revisa <em>Registros de Webhooks</em> antes de producción.</li>
    </ul>
</section>

<section class="manual-block" id="comandos">
    <h2>8. Comandos útiles</h2>
    <ul>
        <li><code>php artisan webhook:status</code> — resumen de actividad del día, últimos eventos y comprobación de Botmaker API, Bitrix API y cola (usa <code>IntegrationProbeService</code>).</li>
        <li><code>php artisan bitrix:test-lead</code> — crea un <strong>lead real</strong> en Bitrix24 con todos los campos del mapeo estándar (incluido EMAIL). Opción <code>--show-fields</code> imprime el JSON enviado a <code>crm.lead.add</code>. Misma lógica que el botón del panel «Pruebas de integración».</li>
        <li><code>php artisan manual:pdf</code> — regenera el PDF estático en <code>public/docs/Manual-Integracion-Bitrix24-Botmaker.pdf</code> (mismo contenido que la descarga en línea desde <code>/manual/descargar-pdf</code>).</li>
    </ul>
</section>

<section class="manual-block" id="pruebas-webservice">
    <h2>9. Pruebas desde el webservice (panel y JSON)</h2>
    <p>Además de Artisan, puedes probar integraciones con la <strong>misma sesión</strong> del panel (cookies de Laravel).</p>

    <h3>9.1 Panel web</h3>
    <p>Ruta: <code>{{ $appBaseUrl }}/monitor/integration-tests</code>. Requiere usuario autenticado, correo verificado y permiso <strong>gestionar configuración</strong> (<code>settings.manage</code>).</p>
    <ul>
        <li><strong>Crear lead de prueba:</strong> llama a Bitrix <code>crm.lead.add</code> con datos de ejemplo (nombre, teléfono, email, campos personalizados del mapeo por defecto, listas, etc.). Úsalo solo en entornos controlados.</li>
        <li><strong>Probar conectividad:</strong> petición ligera a Botmaker, listado mínimo de leads en Bitrix y comprobación de registros «atorados» en estados recibido/procesando.</li>
    </ul>
    <p>Las llamadas a Bitrix pueden tardar varios segundos (timeout HTTP configurable). Si algo falla, revisa la consola del navegador y <code>storage/logs/laravel.log</code>.</p>

    <h3>9.2 Endpoints JSON (HTTP)</h3>
    <p>Para Postman, scripts internos o orquestadores que compartan la sesión del navegador:</p>
    <ul>
        <li><code>POST {{ $appBaseUrl }}/monitor/integration-probes/bitrix-sample</code> — crea el lead de prueba; respuesta JSON con <code>success</code>, <code>lead_id</code>, <code>fields</code>, <code>body</code>, etc. Requiere cabecera <code>X-XSRF-TOKEN</code> (valor de la cookie <code>XSRF-TOKEN</code>) y cookie de sesión. Límite aproximado: 15 peticiones por minuto (throttle).</li>
        <li><code>GET {{ $appBaseUrl }}/monitor/integration-probes/connectivity</code> — estado de Botmaker, Bitrix, cola y resumen de webhooks del día. Límite: 60 peticiones por minuto.</li>
    </ul>
    <p>No están pensados para integración pública sin autenticación; no sustituyen al webhook oficial <code>/webhook/botmaker</code>.</p>

    <h3>9.3 Servicio interno</h3>
    <p>La lógica compartida vive en <code>App\Services\IntegrationProbeService</code>, usada por los comandos, el panel Livewire y el controlador JSON.</p>
</section>
