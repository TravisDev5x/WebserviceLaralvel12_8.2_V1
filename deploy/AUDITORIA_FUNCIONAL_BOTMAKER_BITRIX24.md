# Auditoria funcional - Flujo Botmaker -> WebService -> Bitrix24

Fecha: 2026-04-08  
Alcance: verificacion funcional del flujo solicitado **sin cambios de codigo**.

## Resultado ejecutivo

El flujo base existe y esta operativo, pero **no cumple al 100%** con la especificacion literal en dos puntos principales:

- Validacion de header: se valida `X-Botmaker-Signature` en el controlador, no `auth-bm-token` en middleware.
- Mapeo estricto de campos: el parser actual soporta variantes, pero no garantiza de forma literal `messages[0].message -> COMMENTS` en todos los casos.

---

## Paso 1 - Ruta

- Existe `POST /api/webhook/botmaker`: **SI**
- Apunta a `BotmakerWebhookController@handle`: **SI**
- Mantiene middlewares `verify.webhook.signature` y `throttle:webhooks`: **SI**

Conclusión Paso 1: **Cumple**.

---

## Paso 2 - Validacion de token/header

- `VerifyWebhookSignature` busca header `auth-bm-token`: **NO**
- Usa `AuthorizedToken::isValid('botmaker', $token)` o fallback config: **PARCIAL**
  - Hay validacion de firma/token, pero el comportamiento efectivo del flujo revisado se apoya en `X-Botmaker-Signature` dentro del controlador.

Conclusión Paso 2: **No cumple literal** con la especificacion del header requerido.

---

## Paso 3 - Controlador (`BotmakerWebhookController`)

- Usa `$request->all()` para leer payload: **SI**
- Guarda en `webhook_logs` con `WebhookLog::logIncoming()`: **SI**
- Despacha `ProcessBotmakerPayload` a cola `webhooks`: **SI**
- Responde `200` con `correlation_id`: **SI** (cuando validaciones pasan)

Conclusión Paso 3: **Cumple** en arquitectura asincrona y trazabilidad.

---

## Paso 4 - Job (`ProcessBotmakerPayload`)

- Recibe referencia a `WebhookLog`: **SI** (por `webhookLogId`)
- Lee payload desde `payload_in`: **SI**
- Transforma de Botmaker a Bitrix24: **SI**
- Coincide literal con mapeo pedido:
  - `firstName -> NAME`: **SI**
  - `lastName -> LAST_NAME`: **SI**
  - `contactId -> PHONE`: **PARCIAL** (usa normalizacion por parser, no solo clave literal)
  - `messages[0].message -> COMMENTS`: **PARCIAL** (depende del formato recibido y fallbacks)
- Lee mapeos dinamicos `field_mappings`: **SI**
- Llama `Bitrix24Service` para crear/actualizar lead: **SI**
- Actualiza `webhook_log` con estado `sent/failed`: **SI**
- Si falla, registra en `FailedWebhook`: **SI** (via `failed()` al agotar intentos)

Conclusión Paso 4: **Cumple funcionalmente**, con desalineacion menor frente al contrato estricto de campos literales.

---

## Paso 5 - Servicio Bitrix24 (`Bitrix24Service::createLead`)

- Lee URL desde `config_dynamic()`/`config()`: **SI**
- Hace POST a `.../crm.lead.add`: **SI**
- Formato compatible con Bitrix24: **SI**
- Retorna estructura con `success`, `http_status`, `body`: **SI**

Conclusión Paso 5: **Cumple**.

---

## Checklist final (SI/NO)

1. Ruta exacta y handler correcto: **SI**  
2. Header `auth-bm-token` validado exactamente como se pidio: **NO**  
3. Controller log + dispatch + respuesta inmediata: **SI**  
4. Job transforma y procesa asincrono con trazabilidad: **SI**  
5. Mapeo literal estricto (`messages[0].message`, `contactId`): **NO (PARCIAL)**  
6. Envio a Bitrix24 por `crm.lead.add`: **SI**  
7. Manejo de errores con `FailedWebhook`: **SI**

---

## Brechas detectadas y correccion recomendada (sin aplicar)

1. **Header de seguridad no alineado al contrato**
   - Estado actual: validacion principal efectiva con `X-Botmaker-Signature`.
   - Requerido: `auth-bm-token`.
   - Recomendacion: unificar validacion en middleware para `auth-bm-token` usando `AuthorizedToken::isValid('botmaker', $token)` y dejar fallback en config solo como respaldo controlado.

2. **Mapeo de payload no 100% literal al contrato**
   - Estado actual: parser flexible con variantes de entrada.
   - Requerido: asegurar lectura directa de:
     - `firstName`
     - `lastName`
     - `contactId`
     - `messages[0].message`
   - Recomendacion: priorizar esas claves literales y luego fallback opcional.

---

## Conclusión general

La implementacion actual esta bien encaminada y estable para el flujo asincrono `Botmaker -> Queue -> Bitrix24`, con trazabilidad y manejo de fallos.  
Para declararlo **100% conforme** a la especificacion funcional entregable, faltan ajustar el **header de autenticacion** y la **prioridad del mapeo literal de campos**.

