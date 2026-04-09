# WebService V1 — Conector de Canal Abierto

**Botmaker (WhatsApp) ↔ Bitrix24 CRM** vía `imconnector.*`

Middleware Laravel 12 que conecta Botmaker (WhatsApp) con Bitrix24 como **conector personalizado de Canal Abierto**, replicando la experiencia del conector nativo de WhatsApp de pago.

## Requisitos previos

- PHP 8.2+
- MySQL 8.0+ (o MariaDB 10.6+)
- Composer 2.x
- Dominio con **SSL** (Let's Encrypt / Certbot) — Bitrix24 y Botmaker requieren HTTPS
- Nginx configurado como reverse proxy con SSL hacia Laravel
- Cuenta de Botmaker con credenciales API de envío (no solo recepción)
- Plan de Bitrix24 que permita **Aplicaciones Locales** y API `imconnector.*`
- Canal Abierto ya creado en Bitrix24

## Instalación

```bash
git clone <repo-url> && cd WebserviceLaralvel12_8.2_V1
composer install
cp .env.example .env
php artisan key:generate
```

Configurar variables en `.env` (ver sección Variables de Entorno).

```bash
php artisan migrate
php artisan db:seed    # Crea usuario admin
```

## Variables de Entorno

### Botmaker

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `BOTMAKER_API_URL` | URL base de la API de Botmaker | `https://go.botmaker.com/api/v1.0` |
| `BOTMAKER_API_TOKEN` | Token de acceso a la API | `eyJ...` |
| `BOTMAKER_WEBHOOK_SECRET` | Token para validar webhooks entrantes | `abc123...` |
| `BOTMAKER_SEND_ENDPOINT` | Endpoint de envío de mensajes | `/message/v2` |

### Bitrix24

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `BITRIX24_DOMAIN` | Dominio del portal Bitrix24 | `tuempresa.bitrix24.com` |
| `BITRIX24_CLIENT_ID` | Client ID de la App Local | `local.xxxx` |
| `BITRIX24_CLIENT_SECRET` | Client Secret de la App Local | `yyyy` |
| `BITRIX24_CONNECTOR_ID` | ID del conector personalizado | `botmaker_whatsapp` |
| `BITRIX24_LINE_ID` | ID de la línea de Canal Abierto | `1` |
| `BITRIX24_WEBHOOK_URL` | *(Legacy v1)* URL del webhook CRM | `https://...` |
| `BITRIX24_WEBHOOK_SECRET` | *(Legacy v1)* Secret del webhook | `...` |

## Configuración de la App Local en Bitrix24

1. Iniciar sesión como **administrador**
2. Ir a **Developer resources** → **Other** → **Local application**
3. Tipo: **Servidor**
4. Ruta del controlador: `https://{DOMINIO}/api/bitrix24/handler`
5. Ruta de instalación inicial: `https://{DOMINIO}/api/bitrix24/install`
6. Marcar: **Solo script** (sin interfaz de usuario)
7. Permisos:
   - CRM (`crm`)
   - Canal Abierto (`imopenlines`)
   - Conector de mensajería (`imconnector`)
   - Chat y mensajes (`im`)
8. **Guardar** → copiar `client_id` y `client_secret` al `.env`
9. Click **Reinstalar** → Bitrix24 hará POST al endpoint `/install`
10. Verificar en la tabla `webhook_logs` que el install fue exitoso

### Registrar el conector

```bash
php artisan bitrix24:setup-connector
```

Esto ejecuta: `imconnector.register` → `imconnector.activate` → `imconnector.connector.data.set`.

Luego en Bitrix24:
1. Ir al **Canal Abierto** deseado → **Contact Center**
2. Conectar el conector **Botmaker WhatsApp**
3. Enviar un mensaje de prueba desde WhatsApp

## Flujos de Comunicación

### Flujo A — Cliente escribe por WhatsApp → Agente ve en Bitrix24

```
WhatsApp → Botmaker → POST /api/webhook/botmaker
  → BotmakerWebhookController (valida auth-bm-token)
  → ProcessBotmakerPayload job
  → Bitrix24ConnectorService::sendSingleMessage()
  → imconnector.send.messages (OAuth)
  → Mensaje aparece en Chat del Canal Abierto
  → Bitrix24 crea lead/contacto automáticamente
```

### Flujo B — Agente responde desde Bitrix24 → Cliente recibe en WhatsApp

```
Agente escribe en Chat → Bitrix24 dispara OnImConnectorMessageAdd
  → POST /api/bitrix24/handler
  → Bitrix24OAuthController::handler() (valida application_token)
  → Filtros anti-loop, anti-sistema, anti-duplicado
  → SendBotmakerMessage job
  → BotmakerService::sendMessage()
  → Botmaker envía por WhatsApp
  → imconnector.send.status.delivery confirma entrega
```

## Endpoints API

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/webhook/botmaker` | Recibe webhooks de Botmaker (header `auth-bm-token`) |
| `GET\|POST` | `/api/bitrix24/install` | Recibe tokens OAuth al instalar la App Local |
| `POST` | `/api/bitrix24/handler` | Recibe eventos de la App Local (valida `application_token`) |

## Comandos Artisan

| Comando | Descripción |
|---------|-------------|
| `php artisan bitrix24:setup-connector` | Registra, activa y configura el conector en Bitrix24 |
| `php artisan webhook:status` | Muestra estado de webhooks e integraciones |

## Cola de trabajos

```bash
php artisan queue:work --queue=webhooks --tries=5 --backoff=30,60,300,900,3600
```

Los jobs con reintentos agotados se guardan en `failed_webhooks` para reintento manual desde el panel.

## Panel de Monitoreo

Accesible en `/monitor` (requiere login). Incluye:

- **Dashboard** — KPIs en tiempo real, estado de salud del middleware y del conector
- **Logs** — Todos los webhook logs con detalle
- **Webhooks fallidos** — Reintentar manualmente
- **Configuración** — Tokens, conexiones, mapeos

## Deduplicación

- **Flujo A**: `Cache::lock("botmaker_msg_{$messageId}", 60)` — descarta duplicados de Botmaker
- **Flujo B**: `Cache::put("b24_msg_{$id}", true, 300)` — idempotencia para eventos de Bitrix24
- Driver de cache `database` funciona, pero **Redis** se recomienda en producción para locks atómicos

## Troubleshooting

| Problema | Solución |
|----------|----------|
| Install de App Local falla | Verificar SSL válido, dominio accesible desde internet |
| Token OAuth expirado | Se renueva automáticamente; verificar `BITRIX24_CLIENT_ID/SECRET` |
| Mensajes no llegan al Canal Abierto | Ejecutar `php artisan bitrix24:setup-connector` y verificar conector activo |
| Agente responde pero cliente no recibe | Verificar `BOTMAKER_API_TOKEN` y `BOTMAKER_SEND_ENDPOINT` |
| Mensajes duplicados | Verificar que el cache driver esté funcionando (`php artisan cache:clear`) |
| Queue stuck | Verificar que `queue:work` esté corriendo; revisar `/monitor` |
| `imconnector.status` devuelve error | Verificar que el conector esté conectado al Canal Abierto en Bitrix24 |

## Estructura del Proyecto (archivos clave v2)

```
app/
├── Http/Controllers/
│   ├── Bitrix24/Bitrix24OAuthController.php   # OAuth install + event handler
│   └── Webhook/BotmakerWebhookController.php  # Webhooks de Botmaker
├── Jobs/
│   ├── ProcessBotmakerPayload.php             # Flujo A: Botmaker → Canal Abierto
│   ├── SendBotmakerMessage.php                # Flujo B: Canal Abierto → WhatsApp
│   └── RetryFailedWebhooks.php                # Reintento automático
├── Services/
│   ├── Bitrix24AuthService.php                # OAuth token management
│   ├── Bitrix24ConnectorService.php           # imconnector.* API calls
│   ├── Bitrix24Service.php                    # Legacy CRM methods (test panel)
│   └── BotmakerService.php                    # API de envío de Botmaker
├── Models/
│   └── Bitrix24Token.php                      # Tokens OAuth (encrypted)
├── Livewire/
│   └── ConnectorHealthStatus.php              # Health check del conector v2
├── Exceptions/
│   └── BotmakerApiException.php               # Excepción tipada
└── Legacy/
    ├── BitrixLeadDefaults.php                 # v1: defaults para crm.lead.add
    └── MapBotmakerCanonicalToBitrixLead.php   # v1: mapeo canónico a lead
```
