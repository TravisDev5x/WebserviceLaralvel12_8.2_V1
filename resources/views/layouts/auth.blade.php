<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Acceso' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f5f7fb;
            color: #0f172a;
        }

        .auth-shell {
            width: min(100%, 460px);
            padding: 1rem;
        }

        .auth-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.8rem;
            padding: 1rem;
        }
    </style>
    @livewireStyles
</head>
<body>
    <main class="auth-shell">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
