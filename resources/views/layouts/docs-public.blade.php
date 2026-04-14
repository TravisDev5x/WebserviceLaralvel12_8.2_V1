<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Manual de integración' }}</title>
    <script>
        (() => {
            try {
                const m = localStorage.getItem('themeMode');
                const legacy = localStorage.getItem('ui-theme');
                if (!m && legacy) {
                    localStorage.setItem('themeMode', legacy === 'dark' ? 'dark' : 'light');
                }
            } catch (_) {}
            try {
                const stored = localStorage.getItem('themeMode');
                if (stored ? stored === 'dark'
                    : matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            } catch (_) {}

            const apply = dark => {
                document.documentElement.classList.toggle('dark', dark);
                try { localStorage.setItem('themeMode', dark ? 'dark' : 'light'); } catch (_) {}
            };

            document.addEventListener('basecoat:theme', (event) => {
                const mode = event.detail?.mode;
                apply(mode === 'dark' ? true
                    : mode === 'light' ? false
                    : !document.documentElement.classList.contains('dark'));
            });
        })();
    </script>
    @include('partials.theme-color-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--app-bg);
            color: var(--app-text);
        }
        .docs-header {
            position: sticky; top: 0; z-index: 20;
            background: var(--app-surface);
            border-bottom: 1px solid var(--app-border);
        }
        .docs-header-inner {
            max-width: 900px; margin: 0 auto; padding: 0.85rem 1.25rem;
            display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;
        }
        .docs-main { max-width: 900px; margin: 0 auto; padding: 1.25rem 1.25rem 2.5rem; flex: 1 0 auto; width: 100%; box-sizing: border-box; }
        .muted { color: var(--app-muted); }
        .manual-doc .manual-block { margin-bottom: 2rem; }
        .manual-doc h2 { font-size: 1.35rem; margin: 0 0 0.75rem; line-height: 1.25; }
        .manual-doc h3 { font-size: 1.05rem; margin: 1.25rem 0 0.5rem; color: var(--app-muted); font-weight: 600; }
        .manual-doc p, .manual-doc li { line-height: 1.55; font-size: 0.95rem; }
        .manual-doc ul, .manual-doc ol { padding-left: 1.25rem; margin: 0.5rem 0; }
        .manual-doc code {
            font-size: 0.88em; padding: 0.12rem 0.35rem; border-radius: 0.25rem;
            background: var(--app-border); color: var(--app-text);
        }
        .manual-doc code { background: var(--muted); }
        .manual-doc .table-wrap { overflow-x: auto; margin: 0.75rem 0; }
        .manual-doc .doc-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .manual-doc .doc-table th, .manual-doc .doc-table td {
            border: 1px solid var(--app-border); padding: 0.5rem 0.6rem; text-align: left; vertical-align: top;
        }
        .manual-doc .doc-table thead { background: var(--app-surface); }
        .docs-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; }
        .global-app-footer {
            padding: 0.75rem 1.25rem;
            border-top: 1px solid var(--app-border);
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: var(--app-muted);
            background: var(--app-surface);
        }
    </style>
</head>
<body>
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>
    <header class="docs-header">
        <div class="docs-header-inner">
            <div>
                <p class="muted" style="font-size: 0.75rem; margin: 0;">Middleware Botmaker ↔ Bitrix24</p>
                <h1 style="font-size: 1.05rem; margin: 0;">{{ $title ?? 'Manual de integración' }}</h1>
            </div>
            <div class="docs-actions">
                <a class="btn" href="{{ route('manual.pdf') }}">Generar y descargar PDF</a>
                @if(! empty($staticPdfUrl))
                    <a class="btn" href="{{ $staticPdfUrl }}" download>PDF del proyecto</a>
                @endif
                @if(Route::has('login'))
                    <a class="btn" href="{{ route('login') }}">Iniciar sesión</a>
                @endif
                @include('partials.theme-color-picker')
                <button
                    type="button"
                    id="docs-theme-toggle"
                    aria-label="Cambiar tema"
                    data-tooltip="Cambiar tema"
                    data-side="bottom"
                    onclick="document.dispatchEvent(new CustomEvent('basecoat:theme'))"
                    class="btn-icon-outline size-8"
                >
                    <span class="hidden dark:block"><x-lucide-sun class="size-4" /></span>
                    <span class="block dark:hidden"><x-lucide-moon class="size-4" /></span>
                </button>
            </div>
        </div>
    </header>
    <main id="main-content" class="docs-main manual-doc" tabindex="-1">
        @yield('content')
    </main>
    @include('partials.global-footer')
</body>
</html>
