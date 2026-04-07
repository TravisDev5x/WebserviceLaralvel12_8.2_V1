<div class="diag-root" id="diag-root">
    <div class="diag-head">
        <h3>Wizard de diagnóstico</h3>
        <p>Responde las preguntas y sigue la ruta sugerida hasta resolver.</p>
    </div>

    @if($step === 'root')
        <div class="diag-options">
            <button type="button" class="btn" wire:click="go('a1')">No se crean leads en Bitrix24</button>
            <button type="button" class="btn" wire:click="go('b1')">El cliente no recibe respuesta por WhatsApp</button>
            <button type="button" class="btn" wire:click="go('c')">El tablero muestra todo en cero</button>
            <button type="button" class="btn" wire:click="go('d')">Hay muchos webhooks fallidos</button>
            <button type="button" class="btn" wire:click="go('e')">No puedo entrar al panel</button>
            <button type="button" class="btn" wire:click="go('f')">Cambié un token y dejó de funcionar</button>
        </div>
    @endif

    @if($step === 'a1')
        <div class="diag-card">
            <p><strong>Rama A:</strong> No se crean leads.</p>
            <p>¿Hay registros nuevos en el tablero?</p>
            <div class="diag-options">
                <button type="button" class="btn" wire:click="go('a2')">Sí</button>
                <button type="button" class="btn" wire:click="go('a3')">No</button>
            </div>
        </div>
    @endif

    @if($step === 'a2')
        <div class="diag-card">
            <p>¿El estado del último registro es <strong>Fallido</strong>?</p>
            <div class="diag-options">
                <button type="button" class="btn" wire:click="go('a2-error')">Sí</button>
                <button type="button" class="btn" wire:click="go('a2-ok')">No, dice Enviado</button>
            </div>
        </div>
    @endif

    @if($step === 'a2-error')
        <div class="diag-card">
            <p>¿Qué error muestra?</p>
            <div class="diag-options">
                <button type="button" class="btn" wire:click="go('a2-401')">401 Unauthorized</button>
                <button type="button" class="btn" wire:click="go('a2-500')">500 Error</button>
                <button type="button" class="btn" wire:click="go('a2-timeout')">Connection timeout</button>
                <button type="button" class="btn" wire:click="go('a2-other')">Otro error</button>
            </div>
        </div>
    @endif

    @if($step === 'a2-401')
        <div class="diag-card">
            <p>El token de Bitrix24 expiró o cambió.</p>
            <p>Solución: <strong>Configuración > Conexión Bitrix24</strong> > Actualiza la URL del webhook > Probar conexión.</p>
            <p>Si no tienes la URL nueva, pídela al Admin del CRM.</p>
        </div>
    @endif
    @if($step === 'a2-500') <div class="diag-card"><p>Bitrix24 tiene un problema interno. Espera unos minutos y reintenta. Si persiste, contacta al Admin del CRM.</p></div> @endif
    @if($step === 'a2-timeout') <div class="diag-card"><p>El servidor no puede conectar con Bitrix24. Puede ser red. Contacta a Infraestructura.</p></div> @endif
    @if($step === 'a2-ok') <div class="diag-card"><p>El sistema hizo su parte. El lead debería estar en Bitrix24. Búscalo por teléfono del contacto.</p></div> @endif

    @if($step === 'a2-other')
        <div class="diag-card">
            <label class="label" for="diag-error">Pega el error exacto:</label>
            <textarea id="diag-error" class="textarea" rows="3" wire:model.live="errorDetail" placeholder="Ejemplo: Invalid lead source code"></textarea>
            <p>Copia este mensaje y envíalo al grupo de soporte.</p>
            @if($errorDetail !== '')
                <p><strong>Error capturado:</strong> {{ $errorDetail }}</p>
            @endif
        </div>
    @endif

    @if($step === 'a3')
        <div class="diag-card">
            <p>El sistema no está recibiendo mensajes de Botmaker. Verifica:</p>
            <ol>
                <li>Webhook de Botmaker: <code>https://[dominio]/api/webhook/botmaker</code>.</li>
                <li>HTTPS activo (sin HTTPS no funciona).</li>
                <li>Token idéntico en Botmaker y en <strong>Webhooks autorizados</strong>.</li>
            </ol>
        </div>
    @endif

    @if($step === 'b1')
        <div class="diag-card">
            <p>¿Hay registros tipo <strong>CRM → WhatsApp</strong> en el tablero?</p>
            <div class="diag-options">
                <button type="button" class="btn" wire:click="go('b-red')">Sí, pero en rojo</button>
                <button type="button" class="btn" wire:click="go('b-none')">No hay registros</button>
                <button type="button" class="btn" wire:click="go('b-green')">Sí, en verde</button>
            </div>
        </div>
    @endif
    @if($step === 'b-red')
        <div class="diag-card">
            <p>El sistema intentó enviar pero falló. ¿Qué error muestra?</p>
            <div class="diag-options">
                <button type="button" class="btn" wire:click="go('b-401')">401</button>
                <button type="button" class="btn" wire:click="go('b-other')">Otro</button>
            </div>
        </div>
    @endif
    @if($step === 'b-401') <div class="diag-card"><p>El token API de Botmaker no tiene permisos de envío. Telecomunicaciones debe validar permisos en API Keys o generar uno nuevo y actualizarlo en Conexión Botmaker.</p></div> @endif
    @if($step === 'b-other') <div class="diag-card"><p>Muestra el error, cópialo y repórtalo al grupo de soporte.</p></div> @endif
    @if($step === 'b-none') <div class="diag-card"><p>Bitrix24 no está notificando. Verifica webhook saliente: <code>https://[dominio]/api/webhook/bitrix24</code>.</p></div> @endif
    @if($step === 'b-green') <div class="diag-card"><p>El sistema envió correctamente. Si el cliente no recibe, el problema es de WhatsApp/Botmaker. Contacta a Telecomunicaciones.</p></div> @endif

    @if($step === 'c') <div class="diag-card"><p>Todo en cero indica que ninguna plataforma envía datos. Revisa webhook Botmaker, webhook Bitrix24 y accesibilidad del servidor. Si sigue igual, Infraestructura.</p></div> @endif
    @if($step === 'd') <div class="diag-card"><p>Los fallidos se reintentan hasta 5 veces. Si todos tienen el mismo error (401), corrige token una vez y reintenta todos. Si son timeout/refused, espera recuperación del servicio destino.</p></div> @endif
    @if($step === 'e') <div class="diag-card"><p>Verifica cuenta, recuperación de contraseña, estado activo del usuario y permisos/rol.</p></div> @endif
    @if($step === 'f') <div class="diag-card"><p>Al cambiar token, el anterior muere al instante. Actualiza en ambos lados, copia token completo y usa Probar conexión.</p></div> @endif

    @if($step !== 'root')
        <div class="diag-final">
            <p>¿Se resolvió?</p>
            <div class="diag-options">
                <button type="button" class="btn btn-primary" wire:click="markResolved(true)">Sí</button>
                <button type="button" class="btn" wire:click="markResolved(false)">No</button>
            </div>
            @if($resolved)
                <p class="diag-ok">Genial, el sistema debería estar funcionando. Verifica en el tablero. ✅</p>
            @else
                <div class="diag-warn">
                    <p>Contacta al área responsable:</p>
                    <table class="table-clean" style="min-width: 0;">
                        <thead><tr><th>Problema</th><th>Área</th></tr></thead>
                        <tbody>
                            <tr><td>Conectividad, SSL, timeout</td><td>Infraestructura</td></tr>
                            <tr><td>Permisos/token de Botmaker</td><td>Telecomunicaciones</td></tr>
                            <tr><td>Webhook/permisos de Bitrix24</td><td>Operaciones (Admin CRM)</td></tr>
                        </tbody>
                    </table>
                </div>
            @endif
            <button type="button" class="btn" wire:click="resetWizard">Volver al inicio del diagnóstico</button>
        </div>
    @endif
</div>
