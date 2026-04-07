<div class="integration-manual" id="manual-top">
    <style>
        .integration-manual { --im-radius: 0.65rem; --im-gap: 1.25rem; }
        .integration-manual .im-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: var(--im-gap); }
        .integration-manual .im-layout { display: grid; grid-template-columns: minmax(0, 13.5rem) minmax(0, 1fr); gap: 1.5rem; align-items: start; }
        @media (max-width: 900px) {
            .integration-manual .im-layout { grid-template-columns: 1fr; }
            .integration-manual .im-toc { position: static !important; max-height: none !important; }
        }
        .integration-manual .im-toc {
            position: sticky; top: 4rem; align-self: start; max-height: calc(100vh - 5rem); overflow-y: auto;
            padding: 0.85rem 1rem; border-radius: var(--im-radius); border: 1px solid var(--app-border); background: var(--app-surface);
        }
        .integration-manual .im-toc-title { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--app-muted); margin: 0 0 0.65rem; font-weight: 600; }
        .integration-manual .im-toc ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.35rem; }
        .integration-manual .im-toc a {
            display: block; font-size: 0.84rem; line-height: 1.35; color: var(--app-muted); text-decoration: none; padding: 0.35rem 0.4rem; border-radius: 0.35rem;
        }
        .integration-manual .im-toc a:hover, .integration-manual .im-toc a:focus-visible { background: var(--app-row); color: var(--app-text); outline: none; }
        .integration-manual .im-main { min-width: 0; display: flex; flex-direction: column; gap: 1.5rem; }
        .integration-manual .im-card {
            border-radius: var(--im-radius); border: 1px solid var(--app-border); background: var(--app-surface); padding: 1.15rem 1.25rem;
        }
        .integration-manual .im-card h2 { font-size: 1.15rem; margin: 0 0 0.75rem; line-height: 1.3; color: var(--app-text); }
        .integration-manual .im-card h3 { font-size: 0.95rem; margin: 1rem 0 0.5rem; color: var(--app-text); font-weight: 600; }
        .integration-manual .im-card p, .integration-manual .im-card li { line-height: 1.55; font-size: 0.92rem; margin: 0.5rem 0; color: var(--app-text); }
        .integration-manual .im-card ul, .integration-manual .im-card ol { margin: 0.4rem 0; padding-left: 1.2rem; }
        .integration-manual .im-lead { font-size: 0.95rem; color: var(--app-muted); margin-top: 0 !important; }
        .integration-manual .im-flow-wrap { overflow-x: auto; padding: 0.5rem 0 0.25rem; margin-top: 0.75rem; }
        .integration-manual .im-flow {
            display: flex; flex-wrap: wrap; align-items: stretch; justify-content: center; gap: 0.35rem 0.25rem; min-width: min(100%, 52rem); margin: 0 auto;
        }
        .integration-manual .im-flow-box {
            flex: 1 1 7rem; min-width: 6.5rem; max-width: 10rem; text-align: center; padding: 0.75rem 0.5rem; border-radius: var(--im-radius);
            font-size: 0.82rem; font-weight: 600; line-height: 1.35; color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }
        .integration-manual .im-flow-wa { background: linear-gradient(145deg, #16a34a, #15803d); }
        .integration-manual .im-flow-bm { background: linear-gradient(145deg, #2563eb, #1d4ed8); }
        .integration-manual .im-flow-ws { background: linear-gradient(145deg, #7c3aed, #6d28d9); }
        .integration-manual .im-flow-bx { background: linear-gradient(145deg, #ea580c, #c2410c); }
        .integration-manual .im-flow-conn {
            flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 3.5rem; padding: 0.25rem 0;
        }
        .integration-manual .im-flow-arrows { font-size: 1.35rem; line-height: 1; color: var(--app-muted); letter-spacing: -0.15em; }
        .integration-manual .im-flow-auto { font-size: 0.68rem; color: var(--app-muted); margin-top: 0.2rem; text-transform: lowercase; }
        .integration-manual .im-demo-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.65rem; margin-top: 0.85rem; }
        @media (max-width: 720px) { .integration-manual .im-demo-grid { grid-template-columns: repeat(2, 1fr); } }
        .integration-manual .im-demo-kpi {
            border-radius: var(--im-radius); border: 1px solid var(--app-border); padding: 0.65rem 0.75rem; background: var(--app-row);
        }
        .integration-manual .im-demo-kpi .lbl { font-size: 0.75rem; color: var(--app-muted); margin: 0; }
        .integration-manual .im-demo-kpi .val { font-size: 1.25rem; font-weight: 700; margin: 0.25rem 0 0; }
        .integration-manual .im-demo-kpi .val.ok { color: #16a34a; }
        .integration-manual .im-demo-kpi .val.bad { color: #dc2626; }
        .integration-manual .im-demo-kpi .val.wrn { color: #ca8a04; }
        .integration-manual .im-demo-health { margin-top: 1rem; padding: 0.85rem 1rem; border-radius: var(--im-radius); border: 1px dashed var(--app-border); background: var(--app-row); }
        .integration-manual .im-demo-health p { margin: 0.35rem 0; font-size: 0.88rem; }
        .integration-manual .im-screen-cards { display: grid; gap: 0.85rem; margin-top: 0.75rem; }
        .integration-manual .im-screen-card { border-radius: var(--im-radius); border: 1px solid var(--app-border); padding: 0.9rem 1rem; background: var(--app-row); }
        .integration-manual .im-screen-card h3 { margin: 0 0 0.5rem; font-size: 0.95rem; }
        .integration-manual .im-screen-card dl { margin: 0; font-size: 0.88rem; }
        .integration-manual .im-screen-card dt { font-weight: 600; color: var(--app-muted); margin-top: 0.45rem; }
        .integration-manual .im-screen-card dt:first-child { margin-top: 0; }
        .integration-manual .im-screen-card dd { margin: 0.15rem 0 0; padding: 0; }
        .integration-manual details.im-faq { border: 1px solid var(--app-border); border-radius: var(--im-radius); margin-bottom: 0.5rem; background: var(--app-row); overflow: hidden; }
        .integration-manual details.im-faq summary {
            cursor: pointer; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.9rem; list-style: none; display: flex; align-items: center; gap: 0.5rem;
        }
        .integration-manual details.im-faq summary::-webkit-details-marker { display: none; }
        .integration-manual details.im-faq summary::before { content: '▸'; font-size: 0.85rem; color: var(--app-muted); transition: transform .15s ease; }
        .integration-manual details.im-faq[open] summary::before { transform: rotate(90deg); }
        .integration-manual details.im-faq .im-faq-body { padding: 0 1rem 1rem; border-top: 1px solid var(--app-border); }
        .integration-manual details.im-faq .im-faq-body p { font-size: 0.88rem; }
        .integration-manual .im-table-wrap { overflow-x: auto; margin-top: 0.65rem; -webkit-overflow-scrolling: touch; }
        .integration-manual .im-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 280px; }
        .integration-manual .im-table th, .integration-manual .im-table td { border: 1px solid var(--app-border); padding: 0.5rem 0.6rem; text-align: left; vertical-align: top; }
        .integration-manual .im-table thead th { background: var(--app-row); font-weight: 600; }
        .integration-manual .im-back-top {
            position: fixed; bottom: 1.35rem; right: 1.35rem; z-index: 45; width: 2.75rem; height: 2.75rem; border-radius: 50%; border: 1px solid var(--app-border);
            background: var(--app-surface); color: var(--app-text); box-shadow: 0 4px 14px rgba(0,0,0,.25); cursor: pointer; display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: opacity .2s ease, transform .15s ease;
        }
        .integration-manual .im-back-top:hover { transform: translateY(-2px); }
        .integration-manual .im-back-top:focus-visible { outline: 2px solid var(--app-muted); outline-offset: 2px; opacity: 1; pointer-events: auto; }
        .integration-manual .im-back-top.im-back-top--visible { opacity: 1; pointer-events: auto; }
        .integration-manual .im-muted-note { font-size: 0.82rem; color: var(--app-muted); margin-top: 0.75rem; }
    </style>

    <div class="im-header">
        <div>
            <h2 class="page-title" style="margin-bottom: 0.25rem;">Manual de integración</h2>
            <p class="page-subtitle" style="margin: 0;">Guía en lenguaje sencillo: qué hace el sistema, cómo comprobar que funciona y qué hacer si algo falla.</p>
        </div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
            <input id="manual-search" class="input" type="text" placeholder="Buscar sección...">
            <button class="btn btn-sm" type="button" onclick="window.print()">Imprimir</button>
            <a class="btn btn-sm" href="{{ route('manual.pdf') }}" target="_blank" rel="noopener">Descargar PDF</a>
        </div>
    </div>

    <div class="im-layout">
        <nav class="im-toc" aria-label="Secciones del manual">
            <p class="im-toc-title">En esta página</p>
            <ul>
                <li><a href="#manual-que-hace">¿Qué hace el sistema?</a></li>
                <li><a href="#manual-verificar">¿Cómo verifico que funciona?</a></li>
                <li><a href="#manual-pantallas">Guía de pantallas</a></li>
                <li><a href="#manual-problemas">Si algo falla</a></li>
                <li><a href="#manual-telecom">Para Telecomunicaciones</a></li>
                <li><a href="#manual-operaciones">Para Operaciones (CRM)</a></li>
                <li><a href="#manual-glosario">Glosario</a></li>
                <li><a href="#manual-contacto">Contacto y soporte</a></li>
            </ul>
        </nav>

        <div class="im-main">
            <article class="im-card manual-step" id="manual-que-hace">
                <h2><span class="step-check" data-step="manual-que-hace">☐</span> ¿Qué hace el WebService V1?</h2>
                <p class="im-lead">Este sistema conecta WhatsApp con el CRM Bitrix24 de forma automática.</p>
                <p><strong>Cuando un cliente escribe por WhatsApp:</strong></p>
                <ul>
                    <li>El asistente virtual de Botmaker atiende al cliente y recopila sus datos.</li>
                    <li>Este sistema recibe esos datos automáticamente.</li>
                    <li>Crea un registro (lead) en Bitrix24 con la información del cliente.</li>
                    <li>El agente ve el lead en su CRM sin tener que capturar nada a mano.</li>
                </ul>
                <p><strong>Cuando un agente actúa en Bitrix24:</strong></p>
                <ul>
                    <li>El agente cambia el estatus del lead o escribe una nota.</li>
                    <li>Este sistema detecta el cambio automáticamente.</li>
                    <li>Envía un mensaje al cliente por WhatsApp informándole.</li>
                    <li>El cliente recibe la respuesta sin que el agente abra WhatsApp en su teléfono.</li>
                </ul>
                <p><strong>En resumen:</strong> evita la doble captura y conecta los dos sistemas para que trabajen juntos.</p>
                <div class="im-flow-wrap" aria-hidden="true">
                    <div class="im-flow">
                        <div class="im-flow-box im-flow-wa">Cliente<br>WhatsApp</div>
                        <div class="im-flow-conn"><span class="im-flow-arrows">⇄</span><span class="im-flow-auto">automático</span></div>
                        <div class="im-flow-box im-flow-bm">Botmaker</div>
                        <div class="im-flow-conn"><span class="im-flow-arrows">⇄</span><span class="im-flow-auto">automático</span></div>
                        <div class="im-flow-box im-flow-ws">WebService<br>V1</div>
                        <div class="im-flow-conn"><span class="im-flow-arrows">⇄</span><span class="im-flow-auto">automático</span></div>
                        <div class="im-flow-box im-flow-bx">Bitrix24<br>CRM</div>
                    </div>
                </div>
                <p class="im-muted-note">Las flechas van en ambas direcciones: la información viaja de WhatsApp al CRM y del CRM a WhatsApp sin que usted tenga que moverla a mano.</p>
            </article>

            <article class="im-card manual-step" id="manual-verificar">
                <h2><span class="step-check" data-step="manual-verificar">☐</span> ¿Cómo verifico que todo funciona?</h2>
                <p>En la pantalla principal (<strong>Tablero</strong>) puede ver el estado del sistema al momento:</p>
                <p><strong>Los números de arriba indican:</strong></p>
                <ul>
                    <li><strong>Total webhooks hoy</strong> — Cuántos avisos o eventos se procesaron hoy.</li>
                    <li><strong>Exitosos</strong> — Cuántos terminaron bien.</li>
                    <li><strong>Fallidos</strong> — Cuántos tuvieron algún problema.</li>
                    <li><strong>Pendientes</strong> — Cuántos están en fila esperando su turno.</li>
                </ul>
                <p><strong>El semáforo de salud muestra:</strong></p>
                <ul>
                    <li>🟢 <strong>Verde</strong> — Todo va bien.</li>
                    <li>🟡 <strong>Amarillo</strong> — Hay reintentos pendientes, pero el sistema sigue operando.</li>
                    <li>🔴 <strong>Rojo</strong> — Algo requiere atención.</li>
                </ul>
                <p>Si el semáforo está en verde y los números cambian con el tiempo, el sistema está trabajando correctamente.</p>
                <h3>Ejemplo de cómo se ve el Tablero</h3>
                <p class="im-muted-note" style="margin-top: 0;">Ilustración de referencia (números de ejemplo).</p>
                <div class="im-demo-grid">
                    <div class="im-demo-kpi"><p class="lbl">Total webhooks hoy</p><p class="val">128</p></div>
                    <div class="im-demo-kpi"><p class="lbl">Exitosos</p><p class="val ok">120</p></div>
                    <div class="im-demo-kpi"><p class="lbl">Fallidos</p><p class="val bad">3</p></div>
                    <div class="im-demo-kpi"><p class="lbl">Pendientes en cola</p><p class="val wrn">5</p></div>
                </div>
                <div class="im-demo-health">
                    <p style="margin-top:0;"><strong>Salud del sistema</strong> 🟢</p>
                    <p>Botmaker: conectado · Bitrix24: conectado</p>
                </div>
            </article>

            <article class="im-card manual-step" id="manual-pantallas">
                <h2><span class="step-check" data-step="manual-pantallas">☐</span> Guía de pantallas</h2>
                <p class="im-lead">Cada pantalla tiene un propósito claro. Use esta guía para saber cuándo entrar a cada una.</p>
                <div class="im-screen-cards">
                    <div class="im-screen-card">
                        <h3>Tablero</h3>
                        <dl>
                            <dt>Para qué sirve</dt>
                            <dd>Ver de un vistazo si el sistema está bien y cuántos mensajes se procesaron.</dd>
                            <dt>Cuándo usarla</dt>
                            <dd>Al iniciar el día o cuando quiera comprobar que todo está en orden.</dd>
                            <dt>Qué buscar</dt>
                            <dd>Que los números se muevan y que el semáforo esté en verde.</dd>
                        </dl>
                    </div>
                    <div class="im-screen-card">
                        <h3>Registros de Webhooks</h3>
                        <dl>
                            <dt>Para qué sirve</dt>
                            <dd>Ver el historial de los avisos y eventos que pasaron por el sistema.</dd>
                            <dt>Cuándo usarla</dt>
                            <dd>Cuando alguien diga «no me llegó el lead» o «el cliente no recibió respuesta».</dd>
                            <dt>Qué buscar</dt>
                            <dd>Busque por teléfono o fecha. Si el estado dice enviado (✅), el sistema cumplió su parte. Si dice fallido (❌), abra el detalle para ver qué pasó.</dd>
                        </dl>
                    </div>
                    <div class="im-screen-card">
                        <h3>Webhooks fallidos</h3>
                        <dl>
                            <dt>Para qué sirve</dt>
                            <dd>Ver los mensajes que no se entregaron y que siguen en reintento o requieren revisión.</dd>
                            <dt>Cuándo usarla</dt>
                            <dd>Si en el Tablero el número de fallidos sube.</dd>
                            <dt>Qué buscar</dt>
                            <dd>Muchos fallidos pueden indicar que Botmaker o Bitrix24 tuvieron una caída. El sistema reintenta solo; si se acaban los intentos, el caso quedará aquí para revisión.</dd>
                        </dl>
                    </div>
                    <div class="im-screen-card">
                        <h3>Configuración</h3>
                        <dl>
                            <dt>Para qué sirve</dt>
                            <dd>Cambiar conexiones, llaves de acceso (tokens) y ajustes del sistema.</dd>
                            <dt>Cuándo usarla</dt>
                            <dd>Cuando Telecomunicaciones u Operaciones entreguen una llave nueva o cambie algo en Bitrix24.</dd>
                            <dt>Quién puede usarla</dt>
                            <dd>Los cambios de conexiones y llaves los realiza normalmente un <strong>Administrador</strong>. Si su usuario no ve esas opciones, solicite apoyo a administración o a TI.</dd>
                        </dl>
                    </div>
                </div>
            </article>

            <article class="im-card manual-step" id="manual-problemas">
                <h2><span class="step-check" data-step="manual-problemas">☐</span> Solución de problemas comunes</h2>
                <p class="im-lead">Pulse cada título para ver el detalle. Todo está cerrado por defecto.</p>

                <details class="im-faq">
                    <summary>Los leads no se crean en Bitrix24</summary>
                    <div class="im-faq-body">
                        <p><strong>Lo que ve:</strong> Los clientes escriben por WhatsApp pero no aparecen leads nuevos en el CRM.</p>
                        <p><strong>Por qué suele pasar:</strong> El aviso automático entre Botmaker y este sistema puede estar mal configurado, o la llave de Bitrix24 dejó de ser válida.</p>
                        <p><strong>Qué hacer, paso a paso:</strong></p>
                        <ol>
                            <li>Vaya al <strong>Tablero</strong> y compruebe si hay registros recientes.</li>
                            <li>Si <strong>no</strong> hay registros nuevos, el problema está entre Botmaker y este sistema. Pida a <strong>Telecomunicaciones</strong> que verifiquen que el aviso de salida de Botmaker apunte a la dirección correcta que le indicó Desarrollo.</li>
                            <li>Si <strong>sí</strong> hay registros pero aparecen como fallidos, abra uno y lea el mensaje de error. Si menciona «401» o «no autorizado», la llave de Bitrix24 cambió: vaya a <strong>Configuración → Conexión Bitrix24</strong>, actualice la información y guarde.</li>
                            <li>Use el botón <strong>Probar conexión</strong> en la configuración de Bitrix24 para confirmar que todo responde bien.</li>
                        </ol>
                    </div>
                </details>

                <details class="im-faq">
                    <summary>El cliente no recibe respuesta por WhatsApp</summary>
                    <div class="im-faq-body">
                        <p><strong>Lo que ve:</strong> El agente cambia el lead en Bitrix24 pero el cliente no recibe mensaje en WhatsApp.</p>
                        <p><strong>Por qué suele pasar:</strong> La llave de Botmaker no tiene permiso para enviar mensajes (suele mostrarse como error 401).</p>
                        <p><strong>Qué hacer, paso a paso:</strong></p>
                        <ol>
                            <li>Vaya a <strong>Registros de Webhooks</strong> y busque el evento más reciente relacionado con el aviso de Bitrix hacia Botmaker.</li>
                            <li>Si el estado es fallido y el error dice 401, la llave no puede «hablar» por WhatsApp.</li>
                            <li>Contacte a <strong>Telecomunicaciones</strong> y pídales que, en el panel de Botmaker, revisen la llave de API activa y activen el permiso de envío de mensajes (a veces aparece como envío, escritura o permisos de chat).</li>
                            <li>Es como un teléfono que puede escuchar pero no hablar: hay que habilitarle el permiso para hablar.</li>
                            <li>Cuando Telecomunicaciones confirme el cambio, vaya a <strong>Configuración → Conexión Botmaker</strong> y use <strong>Probar conexión</strong>.</li>
                        </ol>
                    </div>
                </details>

                <details class="im-faq">
                    <summary>Todo funcionaba y dejó de funcionar de repente</summary>
                    <div class="im-faq-body">
                        <p><strong>Lo que ve:</strong> El sistema iba bien y de pronto deja de procesar.</p>
                        <p><strong>Por qué suele pasar:</strong> Botmaker o Bitrix24 pueden tener una falla en sus servidores, o una llave venció.</p>
                        <p><strong>Qué hacer, paso a paso:</strong></p>
                        <ol>
                            <li>Revise el <strong>Tablero</strong> y el semáforo de salud.</li>
                            <li>Si Botmaker aparece en rojo, el inconveniente está del lado de Botmaker: espere o contacte su soporte.</li>
                            <li>Si Bitrix24 aparece en rojo, confirme que puede entrar al CRM con normalidad.</li>
                            <li>Si ambos están en verde pero sigue fallando, vaya a <strong>Configuración → Pruebas de integración</strong> y ejecute las pruebas.</li>
                            <li>Si las pruebas pasan, puede ser algo temporal; los mensajes fallidos se reintentan solos.</li>
                        </ol>
                    </div>
                </details>

                <details class="im-faq">
                    <summary>Hay muchos pendientes en cola y el número no baja</summary>
                    <div class="im-faq-body">
                        <p><strong>Lo que ve:</strong> En el Tablero hay varios pendientes que no se procesan.</p>
                        <p><strong>Por qué suele pasar:</strong> El servicio que procesa la fila de mensajes en el servidor se detuvo.</p>
                        <p><strong>Qué hacer, paso a paso:</strong></p>
                        <ol>
                            <li>Contacte a <strong>Desarrollo o Soporte TI</strong>.</li>
                            <li>Indíqueles que la fila de procesamiento del servidor necesita reiniciarse.</li>
                            <li>No suele ser un problema de configuración en pantalla; es un tema del servidor.</li>
                        </ol>
                    </div>
                </details>

                <details class="im-faq">
                    <summary>Se creó un lead duplicado</summary>
                    <div class="im-faq-body">
                        <p><strong>Lo que ve:</strong> El mismo cliente aparece dos veces en Bitrix24.</p>
                        <p><strong>Por qué suele pasar:</strong> El cliente escribió dos veces muy seguido o el aviso se envió dos veces.</p>
                        <p><strong>Qué hacer, paso a paso:</strong></p>
                        <ol>
                            <li>Puede ocurrir en situaciones normales si hay prisa del cliente o retraso en el procesamiento.</li>
                            <li>El sistema intenta evitar duplicados por teléfono, pero no siempre es posible.</li>
                            <li>Combine o elimine el duplicado en Bitrix24 según su procedimiento interno.</li>
                            <li>Si pasa muy seguido, avise a <strong>Desarrollo</strong> para afinar la ventana de tiempo que usa el sistema.</li>
                        </ol>
                    </div>
                </details>
            </article>

            <article class="im-card manual-step" id="manual-telecom">
                <h2><span class="step-check" data-step="manual-telecom">☐</span> Lo que Telecomunicaciones necesita saber</h2>
                <p>Este sistema se comunica con Botmaker de dos maneras:</p>
                <ol>
                    <li><strong>Recibe mensajes:</strong> Cuando un cliente escribe por WhatsApp, Botmaker avisa a este sistema automáticamente. Para eso, el aviso de salida de Botmaker debe apuntar a la dirección del WebService que le indique Desarrollo.</li>
                    <li><strong>Envía mensajes:</strong> Cuando un agente actúa en el CRM, este sistema envía el mensaje al cliente usando la API de Botmaker. Ahí hace falta una llave con permiso de envío.</li>
                </ol>
                <h3>Si le piden actualizar la llave (token)</h3>
                <ul>
                    <li>Entre al panel de Botmaker → Configuración → API Keys.</li>
                    <li>Copie la llave nueva.</li>
                    <li>En este sistema: <strong>Configuración → Conexión Botmaker</strong>, pegue la llave y guarde.</li>
                    <li>Pulse <strong>Probar conexión</strong> para verificar.</li>
                </ul>
                <h3>Si le piden cambiar la dirección del aviso (webhook)</h3>
                <ul>
                    <li>En Botmaker: Webhooks → aviso de salida.</li>
                    <li>Cambie la dirección por la que le indique Desarrollo y guarde.</li>
                </ul>
                <h3>Problema frecuente: error 401 al enviar mensajes</h3>
                <p>Significa que la llave no tiene permiso para enviar mensajes.</p>
                <ul>
                    <li>Revise en Botmaker → Configuración → API Keys la llave activa.</li>
                    <li>Confirme que tenga habilitado el envío de mensajes.</li>
                    <li>Si no se puede habilitar, cree una llave nueva con los permisos necesarios y compártala con Desarrollo para que la carguen en el sistema.</li>
                </ul>
            </article>

            <article class="im-card manual-step" id="manual-operaciones">
                <h2><span class="step-check" data-step="manual-operaciones">☐</span> Lo que Operaciones necesita saber (Bitrix24)</h2>
                <p>Este sistema se comunica con Bitrix24 de dos maneras:</p>
                <ol>
                    <li><strong>Crea leads:</strong> Cuando llega un contacto de WhatsApp, se crea un lead automático con los datos del cliente.</li>
                    <li><strong>Detecta cambios:</strong> Cuando un agente cambia el estatus o agrega una nota, Bitrix24 avisa a este sistema para notificar al cliente por WhatsApp.</li>
                </ol>
                <h3>Si regeneran o crean un nuevo aviso entrante</h3>
                <ul>
                    <li>Copie la nueva dirección del aviso (suele verse como una dirección web larga que incluye su cuenta de Bitrix24).</li>
                    <li>En este sistema: <strong>Configuración → Conexión Bitrix24</strong>, péguela y guarde.</li>
                    <li>Use <strong>Probar conexión</strong>.</li>
                </ul>
                <h3>Si crean un nuevo aviso saliente</h3>
                <ul>
                    <li>Copie el token de la aplicación que genera Bitrix24.</li>
                    <li>En este sistema: <strong>Configuración → Webhooks autorizados</strong>, agregue el token en la parte correspondiente a avisos salientes de Bitrix24.</li>
                    <li>El sistema aceptará avisos con esa llave automáticamente.</li>
                </ul>
                <h3>Si agregan campos personalizados al lead</h3>
                <ul>
                    <li>Vaya a <strong>Configuración → Mapeo de campos</strong>.</li>
                    <li>Agregue el nuevo campo con su identificador (por ejemplo, un código que Bitrix24 muestra como UF_CRM seguido de números).</li>
                    <li>Indique de qué dato de Botmaker proviene la información.</li>
                </ul>
                <h3>Datos que el sistema suele llenar al crear un lead</h3>
                <ul>
                    <li>Nombre del cliente (según el chat de WhatsApp).</li>
                    <li>Teléfono (número de WhatsApp).</li>
                    <li>Mensaje o motivo de contacto.</li>
                    <li>Fuente del lead (por ejemplo WhatsApp vía Botmaker).</li>
                    <li>Responsable asignado (según lo configurado en este sistema).</li>
                </ul>
            </article>

            <article class="im-card manual-step" id="manual-glosario">
                <h2><span class="step-check" data-step="manual-glosario">☐</span> Glosario de términos</h2>
                <p class="im-lead">Palabras que a veces suenan técnicas, explicadas en cristiano.</p>
                <div class="im-table-wrap">
                    <table class="im-table">
                        <thead>
                            <tr><th>Término</th><th>Qué significa</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Webhook (aviso automático)</td><td>Aviso que un sistema manda a otro cuando pasa algo, como una notificación en el celular.</td></tr>
                            <tr><td>Lead</td><td>Registro de un posible cliente en el CRM: nombre, teléfono, motivo de contacto, etc.</td></tr>
                            <tr><td>Token (llave)</td><td>Contraseña especial para que dos sistemas se reconozcan entre sí, como una llave de una sola puerta.</td></tr>
                            <tr><td>API</td><td>Forma en que dos programas se hablan entre sí en automático, sin intervención manual.</td></tr>
                            <tr><td>Aviso entrante</td><td>Aviso que <strong>llega</strong> a este sistema (por ejemplo, Botmaker avisa que llegó un mensaje nuevo).</td></tr>
                            <tr><td>Aviso saliente</td><td>Aviso que <strong>sale</strong> de otro sistema hacia este (por ejemplo, Bitrix24 avisa que un agente cambió un lead).</td></tr>
                            <tr><td>Cola de procesamiento</td><td>Fila de espera: los mensajes se atienden uno tras otro. Si hay muchos, esperan su turno.</td></tr>
                            <tr><td>Reintento</td><td>Si no se pudo entregar un mensaje, el sistema lo vuelve a intentar solo después de un tiempo.</td></tr>
                            <tr><td>Código 200</td><td>Todo salió bien: el mensaje se entregó correctamente.</td></tr>
                            <tr><td>Código 401</td><td>No autorizado: la llave es incorrecta o no tiene permisos.</td></tr>
                            <tr><td>Código 500</td><td>Error en el servidor del otro sistema: falló por su lado, no por un clic suyo.</td></tr>
                            <tr><td>Flujo A</td><td>Camino del mensaje desde WhatsApp hasta Bitrix24 (crear o actualizar el lead).</td></tr>
                            <tr><td>Flujo B</td><td>Camino del mensaje desde Bitrix24 hasta WhatsApp (responder al cliente).</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="im-card manual-step" id="manual-contacto">
                <h2><span class="step-check" data-step="manual-contacto">☐</span> ¿A quién contacto si algo falla?</h2>
                <div class="im-table-wrap">
                    <table class="im-table">
                        <thead>
                            <tr><th>Situación</th><th>Área</th><th>Qué pedirles</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>No se crean leads en Bitrix24</td><td>Desarrollo / Soporte TI</td><td>Revisar registros de webhooks y la conexión con Bitrix24.</td></tr>
                            <tr><td>La llave de Botmaker fue rechazada</td><td>Telecomunicaciones</td><td>Revisar permisos de la llave de API en Botmaker.</td></tr>
                            <tr><td>Bitrix24 no envía notificaciones</td><td>Operaciones / admin. del CRM</td><td>Verificar que el aviso saliente esté activo y apunte a la dirección correcta.</td></tr>
                            <tr><td>El sistema va lento o no responde</td><td>Infraestructura / TI</td><td>Revisar servidor y servicios.</td></tr>
                            <tr><td>Necesito una llave nueva</td><td>Telecomunicaciones (Botmaker) u Operaciones (Bitrix24)</td><td>Generar llave nueva y actualizarla en Configuración del WebService.</td></tr>
                            <tr><td>No puedo entrar al panel</td><td>Desarrollo / Soporte TI</td><td>Revisar usuario, contraseña y permisos de acceso.</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </div>

    <button type="button" class="im-back-top" id="im-back-top" aria-label="Volver arriba del manual" title="Volver arriba">
        <i data-lucide="arrow-up" style="width:1.25rem;height:1.25rem;"></i>
    </button>

    <script>
        (function () {
            var root = document.getElementById('manual-top');
            if (!root) return;
            root.querySelectorAll('.im-toc a[href^="#"]').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    var id = a.getAttribute('href').slice(1);
                    var el = document.getElementById(id);
                    if (el) {
                        e.preventDefault();
                        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        history.pushState(null, '', '#' + id);
                    }
                });
            });
            var btn = document.getElementById('im-back-top');
            var topEl = document.getElementById('manual-top');
            function toggleBack() {
                if (!btn) return;
                if (window.scrollY > 280) btn.classList.add('im-back-top--visible');
                else btn.classList.remove('im-back-top--visible');
            }
            window.addEventListener('scroll', toggleBack, { passive: true });
            toggleBack();
            if (btn && topEl) {
                btn.addEventListener('click', function () {
                    topEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    if (window.history && window.history.replaceState) window.history.replaceState(null, '', window.location.pathname + window.location.search);
                });
            }
            if (window.lucide) window.lucide.createIcons();

            var searchInput = document.getElementById('manual-search');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    var term = (searchInput.value || '').toLowerCase().trim();
                    root.querySelectorAll('.manual-step').forEach(function (card) {
                        var show = term === '' || card.textContent.toLowerCase().includes(term);
                        card.style.display = show ? '' : 'none';
                    });
                });
            }

            root.querySelectorAll('.step-check').forEach(function (el) {
                var key = 'manual-step:' + el.getAttribute('data-step');
                if (localStorage.getItem(key) === '1') el.textContent = '✓';
                el.style.cursor = 'pointer';
                el.addEventListener('click', function () {
                    var done = el.textContent === '✓';
                    el.textContent = done ? '☐' : '✓';
                    localStorage.setItem(key, done ? '0' : '1');
                });
            });
        })();
    </script>
</div>
