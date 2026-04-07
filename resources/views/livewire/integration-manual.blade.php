<div class="integration-manual" id="manual-top">
    <style>
        .integration-manual { --im-gap: 1rem; }
        .integration-manual .im-head { display: flex; flex-wrap: wrap; gap: .75rem; justify-content: space-between; margin-bottom: 1rem; }
        .integration-manual .im-layout { display: grid; grid-template-columns: 260px minmax(0,1fr); gap: 1rem; }
        .integration-manual .im-nav { position: sticky; top: 4rem; border: 1px solid var(--app-border); border-radius: .65rem; background: var(--app-surface); padding: .75rem; max-height: calc(100vh - 5rem); overflow-y: auto; }
        .integration-manual .im-nav a { display:block; padding:.35rem .5rem; text-decoration:none; color:var(--app-muted); border-radius:.45rem; font-size:.85rem; }
        .integration-manual .im-nav a:hover, .integration-manual .im-nav a.active { background:var(--app-row); color:var(--app-text); }
        .integration-manual .im-card { border:1px solid var(--app-border); border-radius:.7rem; background:var(--app-surface); padding:1rem; margin-bottom:1rem; }
        .integration-manual .im-card h2 { margin:0 0 .6rem; font-size:1.08rem; }
        .integration-manual .im-card h3 { margin:.8rem 0 .45rem; font-size:.92rem; }
        .integration-manual .im-card p, .integration-manual .im-card li { font-size:.9rem; line-height:1.5; }
        .integration-manual .im-flow { display:grid; grid-template-columns: repeat(9,minmax(0,1fr)); gap:.35rem; margin-top:.75rem; align-items:center; }
        .integration-manual .im-box { border-radius:.6rem; padding:.7rem .4rem; text-align:center; font-weight:700; font-size:.78rem; color:#fff; border:0; cursor:pointer; }
        .integration-manual .im-client { background:#16a34a; }
        .integration-manual .im-wa { background:#0891b2; }
        .integration-manual .im-bm { background:#2563eb; }
        .integration-manual .im-sys { background:#7c3aed; }
        .integration-manual .im-bx { background:#ea580c; }
        .integration-manual .im-arrow { text-align:center; font-weight:700; color:var(--app-muted); animation:pulse 1.6s infinite; }
        @keyframes pulse { 0%{opacity:.35;} 50%{opacity:1;} 100%{opacity:.35;} }
        .integration-manual .im-grid-3 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.7rem; }
        .integration-manual .im-kpi { border:1px solid var(--app-border); border-radius:.55rem; background:var(--app-row); padding:.6rem; }
        .integration-manual .im-tag { display:inline-block; border:1px solid var(--app-border); border-radius:999px; padding:.1rem .5rem; font-size:.75rem; }
        .integration-manual details { border:1px solid var(--app-border); border-radius:.6rem; padding:.45rem .65rem; background:var(--app-row); margin-bottom:.45rem; }
        .integration-manual .im-print { margin-top:.7rem; display:flex; justify-content:flex-end; }
        .integration-manual .diag-root .diag-options { display:flex; flex-wrap:wrap; gap:.5rem; margin:.55rem 0; }
        .integration-manual .diag-root .diag-card, .integration-manual .diag-root .diag-warn { border:1px solid var(--app-border); border-radius:.55rem; padding:.65rem; background:var(--app-row); }
        .integration-manual .diag-root .diag-ok { color:#22c55e; }
        .integration-manual .im-help { position: fixed; right: 1rem; bottom: 1rem; z-index: 40; }
        .integration-manual .im-mobile-nav { display:none; margin-bottom:.65rem; }
        @media (max-width: 980px) {
            .integration-manual .im-layout { grid-template-columns: 1fr; }
            .integration-manual .im-nav { position:static; max-height:none; }
            .integration-manual .im-grid-3 { grid-template-columns:1fr; }
            .integration-manual .im-flow { grid-template-columns:repeat(5,minmax(0,1fr)); }
            .integration-manual .im-mobile-nav { display:block; }
        }
        @media print {
            .app-sidebar, .app-header, .im-help, .im-nav, .im-mobile-nav { display:none !important; }
            .integration-manual .im-card { page-break-inside: avoid; }
        }
    </style>

    <div class="im-head">
        <div>
            <h2 class="page-title" style="margin-bottom:.2rem;">Manual de Integración — Autoservicio total</h2>
            <p class="page-subtitle">Si necesitas llamar al desarrollador para algo que no sea una función nueva, el manual falló.</p>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <input class="input" type="text" wire:model.live="search" placeholder="Buscar secciones, términos o áreas...">
            <button type="button" class="btn" onclick="window.print()">Imprimir manual completo</button>
        </div>
    </div>

    <div class="im-mobile-nav">
        <select id="manual-mobile-nav" class="select">
            <option value="">Ir a sección...</option>
            @foreach($this->filteredSections as $section)
                <option value="{{ $section['id'] }}">{{ $section['title'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="im-layout">
        <nav class="im-nav" aria-label="Navegación del manual">
            @foreach($this->filteredSections as $section)
                <a href="#{{ $section['id'] }}" data-target="{{ $section['id'] }}">{{ $section['title'] }}</a>
            @endforeach
        </nav>

        <main>
            <section class="im-card manual-section" id="sec-1" data-title="¿Qué es este sistema y cómo funciona?">
                <h2>1) ¿Qué es este sistema y cómo funciona?</h2>
                <p>Este sistema es un puente automático entre WhatsApp y el CRM.</p>
                <p><strong>Flujo automático 1 — Un cliente escribe por WhatsApp:</strong><br>[Cliente] → [WhatsApp] → [Botmaker] → [Este sistema] → [Bitrix24 CRM]</p>
                <p><strong>Resultado:</strong> aparece un lead nuevo en el CRM sin captura manual.</p>
                <p><strong>Flujo automático 2 — Un agente responde desde el CRM:</strong><br>[Agente en Bitrix24] → [Este sistema] → [Botmaker] → [WhatsApp] → [Cliente]</p>
                <p><strong>Resultado:</strong> el cliente recibe la respuesta por WhatsApp sin abrir WhatsApp en operación.</p>
                <p><strong>Ninguna persona interviene en la transferencia de datos.</strong> Todo es automático.</p>
                <div class="im-flow">
                    <button class="im-box im-client" type="button" onclick="document.getElementById('sec-1').scrollIntoView({behavior:'smooth'})">Cliente</button>
                    <div class="im-arrow">⇄</div>
                    <button class="im-box im-wa" type="button" onclick="document.getElementById('sec-1').scrollIntoView({behavior:'smooth'})">WhatsApp</button>
                    <div class="im-arrow">⇄</div>
                    <button class="im-box im-bm" type="button" onclick="document.getElementById('sec-5').scrollIntoView({behavior:'smooth'})">Botmaker</button>
                    <div class="im-arrow">⇄</div>
                    <button class="im-box im-sys" type="button" onclick="document.getElementById('sec-3').scrollIntoView({behavior:'smooth'})">Este sistema</button>
                    <div class="im-arrow">⇄</div>
                    <button class="im-box im-bx" type="button" onclick="document.getElementById('sec-6').scrollIntoView({behavior:'smooth'})">Bitrix24</button>
                </div>
                <div class="im-print"><button class="btn btn-sm" type="button" onclick="printSection('sec-1')">Imprimir esta sección</button></div>
            </section>

            <section class="im-card manual-section" id="sec-2" data-title="Guía rápida por rol">
                <h2>2) Guía rápida por rol — ¿Qué puedo hacer yo?</h2>
                <div class="im-grid-3">
                    <article class="im-kpi">
                        <h3>Soy de Telecomunicaciones (Botmaker)</h3>
                        <p>✅ Configurar/cambiar tokens de Botmaker<br>✅ Cambiar URL webhook<br>✅ Verificar conexión<br>✅ Diagnosticar por qué no llegan mensajes</p>
                        <button class="btn btn-sm" type="button" onclick="document.getElementById('sec-5').scrollIntoView({behavior:'smooth'})">Ir a mi guía completa</button>
                    </article>
                    <article class="im-kpi">
                        <h3>Soy de Operaciones (Bitrix24 CRM)</h3>
                        <p>✅ Configurar webhooks entrantes/salientes<br>✅ Definir campos del lead<br>✅ Configurar notificaciones<br>✅ Validar creación de leads</p>
                        <button class="btn btn-sm" type="button" onclick="document.getElementById('sec-6').scrollIntoView({behavior:'smooth'})">Ir a mi guía completa</button>
                    </article>
                    <article class="im-kpi">
                        <h3>Soy de Infraestructura</h3>
                        <p>✅ Configurar dominio y SSL<br>✅ Verificar servicios activos<br>✅ Gestionar accesos y permisos del servidor</p>
                        <button class="btn btn-sm" type="button" onclick="document.getElementById('sec-7').scrollIntoView({behavior:'smooth'})">Ir a mi guía completa</button>
                    </article>
                </div>
                <div class="im-print"><button class="btn btn-sm" type="button" onclick="printSection('sec-2')">Imprimir esta sección</button></div>
            </section>

            <section class="im-card manual-section" id="sec-3" data-title="Tablero — Cómo leerlo en 10 segundos">
                <h2>3) Tablero — Cómo leerlo en 10 segundos</h2>
                <div class="im-grid-3">
                    <div class="im-kpi"><p><strong>Contadores</strong></p><p>Estos números muestran cuántos mensajes se procesaron hoy.</p></div>
                    <div class="im-kpi"><p><strong>Semáforo</strong></p><p>🟢 Verde = todo bien, 🔴 Rojo = algo necesita atención.</p></div>
                    <div class="im-kpi"><p><strong>Tabla</strong></p><p>Últimos mensajes procesados. Clic para ver detalle.</p></div>
                </div>
                <h3>Checklist diaria</h3>
                <p>□ Los contadores se mueven<br>□ El semáforo está en verde<br>□ No hay webhooks fallidos nuevos<br>□ El último webhook es de hace menos de una hora (si operación activa)</p>
                <p><strong>Si todo está en verde y los números se mueven → el sistema funciona correctamente.</strong></p>
                <div class="im-print"><button class="btn btn-sm" type="button" onclick="printSection('sec-3')">Imprimir esta sección</button></div>
            </section>

            <section class="im-card manual-section" id="sec-4" data-title="Diagnóstico — Algo no funciona">
                <h2>4) Diagnóstico — Algo no funciona, ¿qué hago?</h2>
                <p>Wizard interactivo de árbol de decisiones. Sigue la ruta y aplica la solución sugerida.</p>
                <livewire:diagnostic-wizard />
                <div class="im-print"><button class="btn btn-sm" type="button" onclick="printSection('sec-4')">Imprimir esta sección</button></div>
            </section>

            <section class="im-card manual-section" id="sec-5" data-title="Guía completa para Telecomunicaciones">
                <h2>5) Guía completa para Telecomunicaciones</h2>
                <h3>5.1 ¿Qué es Botmaker en este sistema?</h3>
                <p>Botmaker conecta WhatsApp con este sistema: avisa mensajes entrantes y recibe instrucciones de envío.</p>
                <h3>5.2 Cómo configurar webhook de salida en Botmaker</h3>
                <ol><li>Entrar al panel de Botmaker.</li><li>Ir a Integraciones/Webhooks.</li><li>URL: <code>https://[dominio]/api/webhook/botmaker</code>.</li><li>Usar token de seguridad.</li><li>Guardar.</li><li>En este sistema: Configuración > Webhooks autorizados > Botmaker > Tokens.</li><li>Registrar el mismo token.</li><li>Guardar.</li><li>Probar enviando mensaje al bot.</li></ol>
                <h3>5.3 Cómo actualizar token de API</h3>
                <ol><li>Copiar Access Token nuevo.</li><li>Configuración > Conexión Botmaker.</li><li>Pegar JWT.</li><li>Guardar.</li><li>Probar conexión.</li></ol>
                <h3>5.4 Cambiar token de seguridad del webhook</h3>
                <ol><li>Copiar token nuevo en Botmaker.</li><li>Actualizar en Webhooks autorizados.</li><li>Desactivar token anterior.</li></ol>
                <h3>5.5 Problemas frecuentes</h3>
                <details><summary>Botmaker no envía datos al sistema</summary><p>Verifica URL HTTPS, token igual en ambos lados y webhook activo.</p></details>
                <details><summary>Error 401 al enviar mensajes</summary><p>Token API sin permisos de envío. Revisar API Keys.</p></details>
                <details><summary>El token dice auth-bm-token</summary><p>Es el encabezado usado por Botmaker; no hay que cambiarlo.</p></details>
                <details><summary>¿Cada cuánto expira el token de API?</summary><p>El JWT expira. Si deja de enviar de repente, renovar y actualizar.</p></details>
                <div class="im-print"><button class="btn btn-sm" type="button" onclick="printSection('sec-5')">Imprimir esta sección</button></div>
            </section>

            <section class="im-card manual-section" id="sec-6" data-title="Guía completa para Operaciones (Bitrix24)">
                <h2>6) Guía completa para Operaciones (Bitrix24)</h2>
                <h3>6.1 ¿Qué es Bitrix24 en este sistema?</h3>
                <p>Es el CRM donde se crean leads y desde donde salen eventos para notificar al cliente.</p>
                <h3>6.2 Webhook entrante</h3>
                <ol><li>Bitrix24 > Aplicaciones > Webhooks.</li><li>Crear/editar webhook entrante.</li><li>Permisos CRM completos.</li><li>Copiar URL.</li><li>Pegar en Configuración > Conexión Bitrix24.</li><li>Probar conexión.</li></ol>
                <h3>6.3 Webhook saliente</h3>
                <ol><li>Crear webhook saliente.</li><li>URL controlador: <code>https://[dominio]/api/webhook/bitrix24</code>.</li><li>Eventos ONCRMLEADUPDATE y ONCRMLEADADD.</li><li>Copiar token app.</li><li>Agregar en Webhooks autorizados > Bitrix24.</li></ol>
                <h3>6.4 Mapeo de campos</h3>
                <p>Configura origen Botmaker → destino Bitrix24. Para personalizados usa ID <code>UF_CRM_...</code>.</p>
                <h3>6.5 Reglas de notificación</h3>
                <p>Configura evento, condición por estatus y mensaje con variables.</p>
                <h3>6.6 Problemas frecuentes</h3>
                <details><summary>No se crean leads</summary><p>Revisar webhook entrante y estado de registros fallidos.</p></details>
                <details><summary>No hay salida a WhatsApp</summary><p>Revisar webhook saliente y token autorizado.</p></details>
                <div class="im-print"><button class="btn btn-sm" type="button" onclick="printSection('sec-6')">Imprimir esta sección</button></div>
            </section>

            <section class="im-card manual-section" id="sec-7" data-title="Guía completa para Infraestructura">
                <h2>7) Guía completa para Infraestructura</h2>
                <h3>7.1 Requisitos del servidor</h3>
                <p>PHP 8.2+, MySQL 8+, Nginx/Apache, SSL obligatorio, dominio apuntado, Supervisor para colas.</p>
                <h3>7.2 Dominio y SSL</h3>
                <pre>sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d webservice.ecd.mx</pre>
                <h3>7.3 Verificar servicios</h3>
                <pre>sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo supervisorctl status</pre>
                <h3>7.4 Reinicio de servicios</h3>
                <pre>sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo supervisorctl restart all
sudo supervisorctl restart webservicev1-worker:*
sudo systemctl restart mysql</pre>
                <h3>7.5 Puertos</h3>
                <table class="table-clean" style="min-width:0;"><thead><tr><th>Puerto</th><th>Uso</th></tr></thead><tbody><tr><td>80/443 entrada</td><td>Recibir webhooks y servir panel</td></tr><tr><td>3306 solo local</td><td>MySQL, no exponer</td></tr><tr><td>22 restringido</td><td>SSH con IPs autorizadas</td></tr></tbody></table>
                <div class="im-print"><button class="btn btn-sm" type="button" onclick="printSection('sec-7')">Imprimir esta sección</button></div>
            </section>

            <section class="im-card manual-section" id="sec-8" data-title="Glosario visual">
                <h2>8) Glosario visual</h2>
                <div class="im-grid-3">
                    <div class="im-kpi"><span class="im-tag">Webhook</span><p>Dos cajas con flecha: aviso automático como SMS del banco.</p></div>
                    <div class="im-kpi"><span class="im-tag">Lead</span><p>Ficha de posible cliente con nombre, teléfono y motivo.</p></div>
                    <div class="im-kpi"><span class="im-tag">Token</span><p>Llave digital para validar identidad entre sistemas.</p></div>
                    <div class="im-kpi"><span class="im-tag">API</span><p>Dos engranes conectados: comunicación automática.</p></div>
                    <div class="im-kpi"><span class="im-tag">SSL/HTTPS</span><p>Candado digital. Sin esto Botmaker no conecta.</p></div>
                    <div class="im-kpi"><span class="im-tag">Cola</span><p>Fila de procesamiento mensaje por mensaje.</p></div>
                    <div class="im-kpi"><span class="im-tag">Flujo A</span><p>Cliente escribe → lead en CRM.</p></div>
                    <div class="im-kpi"><span class="im-tag">Flujo B</span><p>Agente actúa → cliente recibe mensaje.</p></div>
                </div>
                <div class="im-print"><button class="btn btn-sm" type="button" onclick="printSection('sec-8')">Imprimir esta sección</button></div>
            </section>

            <section class="im-card manual-section" id="sec-9" data-title="Tabla de responsabilidades">
                <h2>9) Tabla de responsabilidades</h2>
                <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-bottom:.5rem;">
                    <label for="responsibility-filter">Filtrar por área:</label>
                    <select id="responsibility-filter" class="select" wire:model.live="responsibilityFilter">
                        <option value="todos">Todos</option>
                        <option value="Telecomunicaciones">Telecomunicaciones</option>
                        <option value="Operaciones">Operaciones</option>
                        <option value="Infraestructura">Infraestructura</option>
                        <option value="Admin del sistema">Admin del sistema</option>
                    </select>
                </div>
                <table class="table-clean" style="min-width:0;">
                    <thead><tr><th>Situación</th><th>Quién lo resuelve</th><th>Qué hacer</th></tr></thead>
                    <tbody>
                    @foreach($this->filteredResponsibilities as $row)
                        <tr>
                            <td>{{ $row['situation'] }}</td>
                            <td>{{ $row['owner'] }}</td>
                            <td>{{ $row['action'] }} → sección {{ $row['section'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="im-print"><button class="btn btn-sm" type="button" onclick="printSection('sec-9')">Imprimir esta sección</button></div>
            </section>

            <section class="im-card manual-section" id="sec-10" data-title="Historial de cambios">
                <h2>10) Historial de cambios</h2>
                @if($this->changeHistory->isEmpty())
                    <p>El historial se registra automáticamente cuando se hacen cambios desde la configuración.</p>
                @else
                    <table class="table-clean" style="min-width:0;">
                        <thead><tr><th>Quién</th><th>Qué cambió</th><th>Cuándo</th></tr></thead>
                        <tbody>
                        @foreach($this->changeHistory as $item)
                            <tr>
                                <td>{{ $item['who'] }}</td>
                                <td>{{ $item['what'] }}</td>
                                <td>{{ optional($item['when'])->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
                <div class="im-print"><button class="btn btn-sm" type="button" onclick="printSection('sec-10')">Imprimir esta sección</button></div>
            </section>
        </main>
    </div>

    <div class="im-help">
        <button class="btn btn-primary" type="button" onclick="document.getElementById('sec-4').scrollIntoView({behavior:'smooth'})">¿Necesitas ayuda?</button>
    </div>

    <script>
        function printSection(id) {
            const section = document.getElementById(id);
            if (!section) return;
            const w = window.open('', '_blank', 'width=1024,height=768');
            if (!w) return;
            w.document.write('<html><head><title>Impresión</title><style>body{font-family:Arial,sans-serif;padding:16px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}details{margin:8px 0;}</style></head><body>' + section.innerHTML + '</body></html>');
            w.document.close();
            w.focus();
            w.print();
        }
        (function () {
            const links = document.querySelectorAll('.im-nav a');
            const sections = document.querySelectorAll('.manual-section');
            function activeOnScroll() {
                let current = '';
                sections.forEach((s) => {
                    const top = s.getBoundingClientRect().top;
                    if (top < 160) current = s.id;
                });
                links.forEach((a) => a.classList.toggle('active', a.dataset.target === current));
            }
            links.forEach((a) => {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.getElementById(this.dataset.target);
                    if (target) target.scrollIntoView({ behavior: 'smooth' });
                });
            });
            const select = document.getElementById('manual-mobile-nav');
            if (select) {
                select.addEventListener('change', function () {
                    const target = document.getElementById(this.value);
                    if (target) target.scrollIntoView({ behavior: 'smooth' });
                });
            }
            window.addEventListener('scroll', activeOnScroll, { passive: true });
            activeOnScroll();
        })();
    </script>
</div>
