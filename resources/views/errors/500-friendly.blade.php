<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error del sistema</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
</head>
<body style="min-height:100vh;display:grid;place-items:center;background:#f8fafc;padding:1rem;">
    <div class="card card-pad" style="max-width:680px;width:100%;">
        <h1 style="margin-top:0;">Algo salió mal</h1>
        <p>El equipo técnico ha sido notificado. Intenta de nuevo en unos minutos.</p>
        <a class="btn btn-primary" href="{{ url('/monitor') }}">Volver al monitor</a>
    </div>
</body>
</html>
