<!DOCTYPE html>
<html lang="es" class="auth-layout">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Acceso' }}</title>
    <script>
        (function () {
            try {
                const m = localStorage.getItem('themeMode');
                const legacy = localStorage.getItem('ui-theme');
                if (!m && legacy) {
                    localStorage.setItem('themeMode', legacy === 'dark' ? 'dark' : 'light');
                }
            } catch (_) {}

            try {
                const stored = localStorage.getItem('themeMode');
                const dark = stored ? stored === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {}
        })();
    </script>
    @include('partials.theme-color-head')
    @include('partials.vite-assets')
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--app-bg);
            color: var(--app-text);
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
            border-top: 1px solid var(--app-border);
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: var(--app-muted);
            background: var(--app-surface);
        }

        .auth-card {
            background: var(--app-surface);
            border: 1px solid var(--app-border);
            border-radius: 0.8rem;
            padding: 1rem;
        }
    </style>
    @livewireStyles
</head>
<body>
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>
    <main id="main-content" class="auth-shell" tabindex="-1">
        <div class="auth-shell-inner">
            {{ $slot }}
        </div>
    </main>
    @include('partials.global-footer')
    @livewireScripts
</body>
</html>
