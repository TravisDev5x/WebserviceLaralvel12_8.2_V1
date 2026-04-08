<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #111; line-height: 1.4; }
        h1 { font-size: 14pt; margin: 0 0 8pt; border-bottom: 1px solid #333; padding-bottom: 4pt; }
        .meta { font-size: 8.5pt; color: #444; margin-bottom: 12pt; }
        h2 { font-size: 10.5pt; margin: 10pt 0 4pt; }
        ul { margin: 4pt 0; padding-left: 14pt; }
        li { margin-bottom: 3pt; }
        table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin: 6pt 0; }
        th, td { border: 1px solid #999; padding: 4pt 5pt; text-align: left; vertical-align: top; }
        th { background: #eee; }
        .note { font-size: 8.5pt; color: #333; margin-top: 10pt; padding: 6pt; background: #f5f5f5; border-left: 3pt solid #2563eb; }
        code { font-family: DejaVu Sans Mono, monospace; font-size: 8pt; }
    </style>
</head>
<body>
    <h1>Requisitos de infraestructura (breve)</h1>
    <p class="meta">Middleware Laravel — Botmaker → Bitrix24 · Orientado a Ubuntu Server 24.04 (o similar) · {{ $generatedAt }}</p>

    <h2>1. Software mínimo</h2>
    <table>
        <tr><th>Componente</th><th>Requisito</th></tr>
        <tr><td>PHP</td><td>8.2 o superior (8.3 en Ubuntu 24.04 es válido)</td></tr>
        <tr><td>Extensiones PHP</td><td>curl, mbstring, openssl, pdo, pdo_mysql, xml, bcmath, ctype, fileinfo, tokenizer, json, intl; recomendado php-gd o php-imagick (PDF/DomPDF)</td></tr>
        <tr><td>Base de datos</td><td>MySQL 8+ o MariaDB 10.3+</td></tr>
        <tr><td>Servidor web</td><td>Nginx + PHP-FPM o Apache; HTTPS recomendado para webhooks públicos</td></tr>
        <tr><td>Composer</td><td>2.x</td></tr>
        <tr><td>Node.js / npm</td><td>Solo para compilar front en despliegue (Vite); Node 18+ razonable</td></tr>
    </table>

    <h2>2. Operación obligatoria (este proyecto)</h2>
    <ul>
        <li><strong>Worker de colas:</strong> con <code>QUEUE_CONNECTION=database</code> debe ejecutarse siempre <code>php artisan queue:work --queue=webhooks</code> (Supervisor/systemd). Sin cola activa los webhooks no procesan integraciones.</li>
        <li><strong>Cron Laravel:</strong> cada minuto <code>* * * * * cd /ruta/proyecto &amp;&amp; php artisan schedule:run</code> — hay jobs programados (reintentos de fallidos, alertas).</li>
    </ul>

    <h2>3. Opcional</h2>
    <ul>
        <li>Redis: no obligatorio si caché/sesión/cola usan <code>database</code> en <code>.env</code>.</li>
        <li>SMTP: solo si se usan correos (recuperación contraseña, alertas).</li>
    </ul>

    <h2>4. Post-despliegue</h2>
    <ul>
        <li><code>.env</code> con <code>APP_URL</code> público HTTPS, credenciales DB, Botmaker y Bitrix24.</li>
        <li><code>php artisan migrate</code> (y seeders si aplica).</li>
        <li>Permisos escritura: <code>storage/</code>, <code>bootstrap/cache/</code>.</li>
        <li>Webhook externo apuntando a <code>/webhook/botmaker</code>.</li>
    </ul>

    <p class="note">Documentación ampliada: manual en <code>/manual</code> o PDF <code>public/docs/Manual-Integracion-Bitrix24-Botmaker.pdf</code>.</p>
</body>
</html>
