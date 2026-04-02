<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Manual de integración' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/basecoat.min.js" defer></script>
    <style>
        :root {
            --app-bg: #05070b;
            --app-surface: #090d14;
            --app-text: #f3f4f6;
            --app-muted: #94a3b8;
            --app-border: #1f2937;
        }
        html[data-theme="light"] {
            --app-bg: #f5f7fb;
            --app-surface: #ffffff;
            --app-text: #111827;
            --app-muted: #6b7280;
            --app-border: #e5e7eb;
        }
        body { margin: 0; min-height: 100vh; background: var(--app-bg); color: var(--app-text); }
        .docs-header {
            position: sticky; top: 0; z-index: 20;
            background: var(--app-surface);
            border-bottom: 1px solid var(--app-border);
        }
        .docs-header-inner {
            max-width: 900px; margin: 0 auto; padding: 0.85rem 1.25rem;
            display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;
        }
        .docs-main { max-width: 900px; margin: 0 auto; padding: 1.25rem 1.25rem 2.5rem; }
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
        html[data-theme="light"] .manual-doc code { background: #e5e7eb; }
        .manual-doc .table-wrap { overflow-x: auto; margin: 0.75rem 0; }
        .manual-doc .doc-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .manual-doc .doc-table th, .manual-doc .doc-table td {
            border: 1px solid var(--app-border); padding: 0.5rem 0.6rem; text-align: left; vertical-align: top;
        }
        .manual-doc .doc-table thead { background: var(--app-surface); }
        .docs-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; }
    </style>
</head>
<body>
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
                <button class="btn" type="button" id="docs-theme-toggle" aria-label="Cambiar tema">Tema</button>
            </div>
        </div>
    </header>
    <main class="docs-main manual-doc">
        @yield('content')
    </main>
    <script>
        (function () {
            var root = document.documentElement;
            var saved = localStorage.getItem('ui-theme') || 'dark';
            root.setAttribute('data-theme', saved);
            var btn = document.getElementById('docs-theme-toggle');
            if (btn) btn.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                localStorage.setItem('ui-theme', next);
            });
        })();
    </script>
</body>
</html>
