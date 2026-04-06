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
            display: flex;
            flex-direction: column;
            background: #f5f7fb;
            color: #0f172a;
        }

        .auth-shell {
            flex: 1;
            display: grid;
            place-items: center;
            width: 100%;
            padding: 1rem;
            box-sizing: border-box;
        }

        .auth-shell-inner {
            width: min(100%, 460px);
        }

        .global-app-footer {
            padding: 0.75rem 1rem;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: #64748b;
            background: #fff;
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
        <div class="auth-shell-inner">
            {{ $slot }}
        </div>
    </main>
    @include('partials.global-footer')
    @livewireScripts
</body>
</html>
