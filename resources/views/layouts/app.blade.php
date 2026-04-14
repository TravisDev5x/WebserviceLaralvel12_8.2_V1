<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Monitor de Webhooks' }}</title>
    {{-- Basecoat theme: themeMode + class dark (ver https://basecoatui.com/components/theme-switcher ) --}}
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
    @include('partials.vite-assets')
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--app-bg);
            color: var(--app-text);
        }

        .app-main {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: var(--app-bg);
            color: var(--app-text);
        }

        .app-header {
            position: sticky;
            top: 0;
            z-index: 20;
            height: 3.35rem;
            background: var(--app-surface);
            border-bottom: 1px solid var(--app-border);
        }

        .app-header-inner {
            height: 100%;
            width: 100%;
            box-sizing: border-box;
            padding: 0 1.4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .content-shell {
            width: 100%;
            box-sizing: border-box;
            padding: 1.25rem 1.4rem 1.6rem;
            flex: 1 1 auto;
        }

        .global-app-footer {
            margin-top: auto;
            width: 100%;
            box-sizing: border-box;
            padding: 0.75rem 1.4rem;
            border-top: 1px solid var(--app-border);
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: var(--app-muted);
            background: var(--app-surface);
        }

        .app-breadcrumb {
            margin: -0.35rem 0 0.85rem;
        }

        .app-breadcrumb-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem 0.45rem;
            font-size: 0.8rem;
            line-height: 1.35;
            color: var(--app-muted);
        }

        .app-breadcrumb-item {
            display: inline-flex;
            align-items: center;
            max-width: 100%;
        }

        .app-breadcrumb-link {
            color: var(--app-muted);
            text-decoration: none;
            border-radius: 0.25rem;
            transition: color 0.12s ease, background-color 0.12s ease;
        }

        .app-breadcrumb-link:hover {
            color: var(--app-text);
        }

        .app-breadcrumb-current {
            color: var(--app-text);
            font-weight: 600;
            word-break: break-word;
        }

        .app-breadcrumb-sep {
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
            color: var(--app-muted);
            opacity: 0.75;
            list-style: none;
        }

        .app-breadcrumb-chevron {
            display: block;
            width: 0.95rem;
            height: 0.95rem;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.9rem;
            margin-bottom: 1rem;
        }

        .page-title {
            margin: 0;
            line-height: 1.2;
        }

        .page-subtitle {
            margin: 0;
            color: var(--app-muted);
        }

        .grid-auto {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        }

        .card-pad {
            padding: 1rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .table-clean {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .table-clean th,
        .table-clean td {
            text-align: left;
            vertical-align: middle;
            padding: 0.68rem 0.6rem;
        }

        .table-clean thead tr {
            border-bottom: 1px solid var(--app-border);
        }

        .table-clean tbody tr {
            border-bottom: 1px solid var(--app-border);
        }

        .users-list-section .table-clean thead th {
            font-weight: 600;
            color: var(--app-muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .users-list-section .table-clean th .th-sort-btn {
            background: transparent;
            border: none;
            padding: 0;
            margin: 0;
            font: inherit;
            font-size: inherit;
            font-weight: 600;
            color: inherit;
            cursor: pointer;
            text-align: left;
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
        }

        .users-list-section .table-clean th .th-sort-btn:hover {
            color: var(--app-text);
        }

        .users-section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }

        .users-section-head-main {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.55rem 1rem;
            min-width: 0;
        }

        .users-section-head-main h3 {
            margin: 0;
            line-height: 1.2;
        }

        .users-meta-badges {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem;
        }

        .users-filters-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 0.6rem;
            margin-bottom: 0.65rem;
        }

        .users-search-wrap {
            flex: 1 1 200px;
            min-width: min(100%, 200px);
            max-width: 340px;
        }

        .users-filters-selects {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            flex: 1 1 280px;
        }

        .users-filters-selects > div {
            flex: 0 1 auto;
        }

        .users-filters-selects .select {
            min-width: 9.5rem;
            max-width: 100%;
        }

        .users-clear-wrap {
            flex-shrink: 0;
            margin-left: auto;
            padding-top: 1.02rem;
        }

        @media (max-width: 640px) {
            .users-clear-wrap {
                margin-left: 0;
                width: 100%;
                padding-top: 0;
            }
            .users-clear-wrap .btn {
                width: 100%;
            }
        }

        .muted {
            color: var(--app-muted);
        }

        .badge-soft {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid var(--app-border);
            padding: 0.18rem 0.56rem;
            font-size: 0.78rem;
            line-height: 1.1;
        }

        .sidebar-nav-badge {
            margin-left: auto;
            min-width: 1.35rem;
            height: 1.35rem;
            padding: 0 0.35rem;
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            font-size: 0.68rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-health {
            margin-left: auto;
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .sidebar-health--ok {
            background: #16a34a;
        }

        .sidebar-health--bad {
            background: #dc2626;
        }

        .menu-toggle-btn {
            display: inline-flex;
            align-items: center;
        }

        .clickable-row {
            transition: background-color .12s ease;
        }

        .clickable-row:hover {
            background: var(--app-row);
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            color: var(--card-foreground);
        }

        .manual-doc .manual-block { margin-bottom: 1.5rem; }
        .manual-doc h2 { font-size: 1.2rem; margin: 0 0 0.6rem; line-height: 1.3; }
        .manual-doc h3 { font-size: 0.95rem; margin: 1rem 0 0.4rem; color: var(--app-muted); font-weight: 600; }
        .manual-doc p, .manual-doc li { line-height: 1.5; font-size: 0.92rem; }
        .manual-doc ul, .manual-doc ol { margin: 0.4rem 0; padding-left: 1.2rem; }
        .manual-doc code { font-size: 0.84em; padding: 0.1rem 0.3rem; border-radius: 0.25rem; background: var(--app-row); }
        .manual-doc .doc-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 520px; }
        .manual-doc .doc-table th, .manual-doc .doc-table td {
            border: 1px solid var(--app-border); padding: 0.45rem 0.5rem; text-align: left; vertical-align: top;
        }

        @media (min-width: 768px) {
            .menu-toggle-btn {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .app-header-inner {
                padding: 0 0.9rem;
            }

            .content-shell {
                padding: 1rem 0.9rem 1.3rem;
            }

            .global-app-footer {
                padding: 0.75rem 0.9rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
    @livewireStyles
</head>
<body>
    @php
        // Definir siempre en ámbito global del layout: con Livewire + <x-lucide> dentro del footer,
        // @php() anidado en @if puede dejar $accountUser indefinido en vistas compiladas (500 en servidor).
        $accountUser = auth()->check() ? auth()->user() : null;
        $accountName = 'Cuenta';
        $accountSubtitle = 'usuario';
        if ($accountUser !== null) {
            $accountName = (string) ($accountUser->name ?? 'Cuenta');
            $accountLocal = $accountUser->email
                ? (string) \Illuminate\Support\Str::before((string) $accountUser->email, '@')
                : '';
            $accountRole = \Illuminate\Support\Str::lower((string) ($accountUser->role ?? 'usuario'));
            $accountSubtitle = $accountLocal !== '' ? '@'.$accountLocal : $accountRole;
        }
    @endphp
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>
    <aside id="main-navigation" class="sidebar" data-side="left" aria-hidden="false" data-collapsed="false">
        <nav aria-label="Sidebar navigation">
            <header class="flex flex-col gap-2 p-2">
                <button
                    type="button"
                    id="sidebar-collapse-toggle"
                    class="btn-icon-ghost size-8 max-md:hidden"
                    aria-label="Contraer barra lateral"
                    data-tooltip="Contraer barra lateral"
                    data-side="right"
                    onclick="document.dispatchEvent(new CustomEvent('basecoat:sidebar-collapse'))"
                >
                    {{-- sidebar-close / sidebar-open: compatibles con blade-lucide-icons más viejos; panel-left-close falla en servidor sin SVG reciente. --}}
                    <span class="sidebar-collapse-icon-expanded inline-flex"><x-lucide-sidebar-close class="size-5 shrink-0" aria-hidden="true" /></span>
                    <span class="sidebar-collapse-icon-collapsed hidden inline-flex"><x-lucide-sidebar-open class="size-5 shrink-0" aria-hidden="true" /></span>
                </button>
            </header>
            <section class="scrollbar">
                @php($canMonitor = user_can('monitor.view'))
                @php($canLogs = user_can('logs.view'))
                @php($canFailed = user_can('failed.view'))
                @php($canSettings = user_can('settings.manage'))
                @php($canMappings = user_can('mappings.manage'))
                @php($canUsers = user_can('users.manage'))
                @php($isAdmin = auth()->check() && (string) (auth()->user()->role ?? '') === 'admin')
                @php($fp = (int) ($sidebarFailedPendingCount ?? 0))

                @if($canMonitor || $canLogs || $canFailed)
                    <div role="group" aria-labelledby="group-label-monitoreo">
                        <h3 id="group-label-monitoreo">Monitoreo</h3>
                        <ul>
                            @if($canMonitor)
                                <li><a href="{{ url('/monitor') }}" aria-label="Tablero" data-tooltip="Tablero" data-side="right" @if(request()->is('monitor')) aria-current="page" @endif><x-lucide-layout-dashboard class="size-4 shrink-0" aria-hidden="true" /><span class="sidebar-link-label" aria-hidden="true">Tablero</span></a></li>
                                <li><a href="{{ url('/monitor/manual') }}" aria-label="Manual de integración" data-tooltip="Manual de integración" data-side="right" @if(request()->is('monitor/manual')) aria-current="page" @endif><x-lucide-book-open class="size-4 shrink-0" aria-hidden="true" /><span class="sidebar-link-label" aria-hidden="true">Manual de integración</span></a></li>
                            @endif
                            @if($canLogs)
                                <li><a href="{{ url('/monitor/logs') }}" aria-label="Registros de Webhooks" data-tooltip="Registros de Webhooks" data-side="right" @if(request()->is('monitor/logs*')) aria-current="page" @endif><x-lucide-list-collapse class="size-4 shrink-0" aria-hidden="true" /><span class="sidebar-link-label" aria-hidden="true">Registros de Webhooks</span></a></li>
                            @endif
                            @if($canFailed)
                                <li>
                                    <a href="{{ url('/monitor/failed') }}" aria-label="Webhooks fallidos" data-tooltip="Webhooks fallidos" data-side="right" @if(request()->is('monitor/failed*')) aria-current="page" @endif>
                                        <x-lucide-shield-alert class="size-4 shrink-0" aria-hidden="true" />
                                        <span class="sidebar-link-label" aria-hidden="true">Webhooks Fallidos</span>
                                        @if($fp > 0)<span class="sidebar-nav-badge" title="Pendientes">{{ $fp > 99 ? '99+' : $fp }}</span>@endif
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif

                @if($canSettings && $isAdmin)
                    <div role="group" aria-labelledby="group-label-configuracion">
                        <h3 id="group-label-configuracion">Configuración</h3>
                        <ul>
                            <li><a href="{{ url('/monitor/settings') }}" aria-label="Centro de configuración" data-tooltip="Centro de configuración" data-side="right" @if(request()->is('monitor/settings') && ! request()->is('monitor/settings/*')) aria-current="page" @endif><x-lucide-layout-grid class="size-4 shrink-0" aria-hidden="true" /><span class="sidebar-link-label" aria-hidden="true">Centro de configuración</span></a></li>
                            <li>
                                <a href="{{ url('/monitor/settings/botmaker') }}" aria-label="Conexión Botmaker" data-tooltip="Conexión Botmaker" data-side="right" @if(request()->is('monitor/settings/botmaker')) aria-current="page" @endif>
                                    <x-lucide-message-circle class="size-4 shrink-0" aria-hidden="true" />
                                    <span class="sidebar-link-label" aria-hidden="true">Conexión Botmaker</span>
                                    <span class="sidebar-health sidebar-health--{{ ($sidebarHealthBotmaker ?? false) ? 'ok' : 'bad' }}" title="Estado configuración"></span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/monitor/settings/bitrix24') }}" aria-label="Conexión Bitrix24" data-tooltip="Conexión Bitrix24" data-side="right" @if(request()->is('monitor/settings/bitrix24')) aria-current="page" @endif>
                                    <x-lucide-contact class="size-4 shrink-0" aria-hidden="true" />
                                    <span class="sidebar-link-label" aria-hidden="true">Conexión Bitrix24</span>
                                    <span class="sidebar-health sidebar-health--{{ ($sidebarHealthBitrix ?? false) ? 'ok' : 'bad' }}" title="Estado configuración"></span>
                                </a>
                            </li>
                            <li><a href="{{ route('monitor.tokens') }}" aria-label="Webhooks autorizados" data-tooltip="Webhooks autorizados" data-side="right" @if(request()->is('monitor/settings/tokens')) aria-current="page" @endif><x-lucide-key class="size-4 shrink-0" aria-hidden="true" /><span class="sidebar-link-label" aria-hidden="true">Webhooks autorizados</span></a></li>
                            @if($canMappings)<li><a href="{{ url('/monitor/field-mappings') }}" aria-label="Mapeo de campos" data-tooltip="Mapeo de campos" data-side="right" @if(request()->is('monitor/field-mappings*')) aria-current="page" @endif><x-lucide-git-compare-arrows class="size-4 shrink-0" aria-hidden="true" /><span class="sidebar-link-label" aria-hidden="true">Mapeo de campos</span></a></li>@endif
                            <li><a href="{{ url('/monitor/settings/retry') }}" aria-label="Reintentos" data-tooltip="Reintentos" data-side="right" @if(request()->is('monitor/settings/retry')) aria-current="page" @endif><x-lucide-timer class="size-4 shrink-0" aria-hidden="true" /><span class="sidebar-link-label" aria-hidden="true">Reintentos</span></a></li>
                            <li><a href="{{ route('integration-tests.panel') }}" aria-label="Pruebas de integración" data-tooltip="Pruebas de integración" data-side="right" @if(request()->is('monitor/settings/test') || request()->is('monitor/integration-probes*')) aria-current="page" @endif><x-lucide-flask-conical class="size-4 shrink-0" aria-hidden="true" /><span class="sidebar-link-label" aria-hidden="true">Pruebas de integración</span></a></li>
                        </ul>
                    </div>
                @elseif($canSettings && ! $isAdmin)
                    <div role="group" aria-labelledby="group-label-configuracion-op">
                        <h3 id="group-label-configuracion-op">Configuración</h3>
                        <ul>
                            <li><a href="{{ url('/monitor/settings') }}" aria-label="Centro de configuración" data-tooltip="Centro de configuración" data-side="right" @if(request()->is('monitor/settings')) aria-current="page" @endif><x-lucide-layout-grid class="size-4 shrink-0" aria-hidden="true" /><span class="sidebar-link-label" aria-hidden="true">Centro de configuración</span></a></li>
                        </ul>
                    </div>
                @endif

                @if($canUsers)
                    <div role="group" aria-labelledby="group-label-sistema">
                        <h3 id="group-label-sistema">Sistema</h3>
                        <ul>
                            <li><a href="{{ url('/monitor/access-control') }}" aria-label="Usuarios, roles y permisos" data-tooltip="Usuarios, roles y permisos" data-side="right" @if(request()->is('monitor/access-control*')) aria-current="page" @endif><x-lucide-users-round class="size-4 shrink-0" aria-hidden="true" /><span class="sidebar-link-label" aria-hidden="true">Usuarios, roles y permisos</span></a></li>
                        </ul>
                    </div>
                @endif
            </section>
            <footer>
                @if($accountUser !== null)
                <div id="sidebar-account-dropdown" class="dropdown-menu w-full min-w-0 px-2 pb-2">
                    <button
                        type="button"
                        id="sidebar-account-trigger"
                        aria-haspopup="menu"
                        aria-controls="sidebar-account-menu"
                        aria-expanded="false"
                        aria-label="Menú de cuenta"
                        class="sidebar-account-trigger ring-sidebar-ring flex w-full min-w-0 items-center gap-2 overflow-hidden rounded-md p-2 text-left text-sm outline-hidden transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2"
                        data-tooltip="{{ $accountName }} — {{ $accountSubtitle }}"
                        data-side="right"
                    >
                        <span class="sidebar-account-collapsed-only shrink-0" aria-hidden="true">
                            <x-lucide-circle-user class="size-4" />
                        </span>
                        <span class="sidebar-account-summary flex min-w-0 flex-1 flex-col items-start gap-0.5 leading-tight">
                            <span class="truncate font-medium text-sidebar-foreground">{{ $accountName }}</span>
                            <span class="text-sidebar-foreground/70 truncate text-xs font-normal">{{ $accountSubtitle }}</span>
                        </span>
                        <x-lucide-chevrons-up-down class="sidebar-account-chevron size-4 shrink-0 text-sidebar-foreground/70" aria-hidden="true" />
                    </button>
                    <div id="sidebar-account-popover" data-popover aria-hidden="true" class="min-w-56" data-side="top" data-align="end">
                        <div role="menu" id="sidebar-account-menu" aria-labelledby="sidebar-account-trigger">
                            <a href="{{ route('profile.edit') }}" role="menuitem" id="sidebar-menu-profile" class="inline-flex w-full items-center gap-2">
                                <x-lucide-circle-user class="size-4 shrink-0" aria-hidden="true" />
                                Mi perfil
                            </a>
                            <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form" class="m-0 hidden">@csrf</form>
                            <button type="submit" form="sidebar-logout-form" role="menuitem" id="sidebar-menu-logout" class="inline-flex w-full items-center gap-2">
                                <x-lucide-log-out class="size-4 shrink-0" aria-hidden="true" />
                                Cerrar sesión
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </footer>
        </nav>
    </aside>

    <main id="main-content" class="app-main" tabindex="-1">
        <header class="app-header">
            <div class="app-header-inner">
                <div>
                    <p class="muted" style="font-size: 0.78rem; margin: 0;">Monitor del Middleware</p>
                    <h1 style="font-size: 1rem; margin: 0;">{{ $title ?? 'Panel de Webhooks' }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="sidebar-mobile-toggle" class="btn-icon-ghost menu-toggle-btn size-8" onclick="document.dispatchEvent(new CustomEvent('basecoat:sidebar', { detail: { id: 'main-navigation' } }))" aria-label="Abrir menu lateral">
                        <x-lucide-menu class="size-5 shrink-0" aria-hidden="true" />
                    </button>
                    @include('partials.theme-color-picker')
                    <button type="button" id="theme-toggle" aria-label="Cambiar tema" data-tooltip="Cambiar tema" data-side="bottom" onclick="document.dispatchEvent(new CustomEvent('basecoat:theme'))" class="btn-icon-outline size-8">
                        <span class="hidden dark:block"><x-lucide-sun class="size-4" /></span>
                        <span class="block dark:hidden"><x-lucide-moon class="size-4" /></span>
                    </button>
                </div>
            </div>
        </header>

        <div class="content-shell">
            @if(session('error'))
                <div class="alert-destructive mb-3" role="alert">
                    <h2 class="text-sm font-semibold leading-snug m-0">{{ session('error') }}</h2>
                </div>
            @endif
            @if(session('success'))
                <div class="alert mb-3" role="status">
                    <h2 class="text-sm font-semibold leading-snug m-0">{{ session('success') }}</h2>
                </div>
            @endif
            @if(! empty($breadcrumbs ?? []))
                <x-breadcrumb :items="$breadcrumbs" />
            @endif
            {{ $slot }}
        </div>
        @include('partials.global-footer')
    </main>

    @livewireScripts
    <script>
        (function () {
            function bindAppShell() {
                if (!window.__appPasswordToggleBound) {
                    document.addEventListener('click', (event) => {
                        const button = event.target instanceof Element
                            ? event.target.closest('[data-toggle-password]')
                            : null;
                        if (!button) return;
                        const inputId = button.getAttribute('data-toggle-password');
                        if (!inputId) return;
                        const input = document.getElementById(inputId);
                        if (!input) return;
                        const isPassword = input.getAttribute('type') === 'password';
                        input.setAttribute('type', isPassword ? 'text' : 'password');
                        button.textContent = isPassword ? 'Ocultar' : 'Ver';
                    });
                    window.__appPasswordToggleBound = true;
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bindAppShell);
            } else {
                bindAppShell();
            }
        })();
    </script>
</body>
</html>
