<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Monitor de Webhooks' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css">
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/basecoat.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/js/sidebar.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
    <style>
        :root {
            --sidebar-width: 15rem;
            --app-bg: #05070b;
            --app-surface: #090d14;
            --app-text: #f3f4f6;
            --app-muted: #94a3b8;
            --app-border: #1f2937;
            --app-row: #151b23;
            --sidebar-bg: #0b1018;
            --sidebar-row: #181f2a;
            --sidebar-active: #202836;
        }

        html[data-theme="light"] {
            --app-bg: #f5f7fb;
            --app-surface: #ffffff;
            --app-text: #111827;
            --app-muted: #6b7280;
            --app-border: #e5e7eb;
            --app-row: #f3f4f6;
            --sidebar-bg: #eef2ff;
            --sidebar-row: #e0e7ff;
            --sidebar-active: #c7d2fe;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--app-bg);
            color: var(--app-text);
        }

        .app-layout {
            min-height: 100vh;
            display: grid;
            grid-template-columns: var(--sidebar-width) minmax(0, 1fr);
        }

        .app-header {
            position: sticky;
            top: 0;
            z-index: 20;
            height: 3.35rem;
            background: var(--app-surface);
            border-bottom: 1px solid var(--app-border);
        }

        .app-content-wrap {
            background: var(--app-bg);
            min-width: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .app-header-inner {
            height: 100%;
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 1.4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .content-shell {
            width: 100%;
            max-width: 1240px;
            margin: 0 auto;
            padding: 1.25rem 1.4rem 1.6rem;
            flex: 1 0 auto;
        }

        .global-app-footer {
            margin-top: auto;
            padding: 0.75rem 1.4rem;
            border-top: 1px solid var(--app-border);
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: var(--app-muted);
            background: var(--app-surface);
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

        .app-sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--app-border);
            padding: 0.85rem 0.6rem;
            z-index: 35;
        }

        .app-sidebar nav {
            height: 100%;
            overflow-y: auto;
        }

        .sidebar-title {
            margin: 0.4rem 0.45rem 0.55rem;
            color: var(--app-muted);
            font-size: 0.72rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            font-weight: 600;
        }

        .sidebar-nav {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 0.3rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            min-height: 2.35rem;
            padding: 0.5rem 0.72rem;
            border-radius: 0.55rem;
            color: var(--app-text);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background-color .12s ease;
        }

        .sidebar-link:hover {
            background: var(--sidebar-row);
        }

        .sidebar-link[aria-current="page"] {
            background: var(--sidebar-active);
            color: var(--app-text);
            border: 1px solid var(--app-border);
        }

        .sidebar-link i {
            width: 1rem;
            height: 1rem;
            color: var(--app-muted);
        }

        .sidebar-link[aria-current="page"] i {
            color: var(--app-text);
        }

        .menu-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            opacity: 0;
            pointer-events: none;
            transition: opacity .18s ease;
            z-index: 30;
        }

        .clickable-row {
            transition: background-color .12s ease;
        }

        .clickable-row:hover {
            background: var(--app-row);
        }

        .card {
            background: var(--app-surface);
            border: 1px solid var(--app-border);
            color: var(--app-text);
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

        @media (min-width: 1025px) {
            .menu-toggle-btn {
                display: none !important;
            }

            .sidebar-backdrop {
                display: none;
            }
        }

        @media (max-width: 1024px) {
            .app-layout {
                display: block;
            }

            .app-sidebar {
                position: fixed;
                left: 0;
                width: min(88vw, 18rem);
                transform: translateX(-100%);
                transition: transform .2s ease;
            }

            body.sidebar-open .app-sidebar {
                transform: translateX(0);
            }

            body.sidebar-open .sidebar-backdrop {
                opacity: 1;
                pointer-events: auto;
            }
        }

        @media (max-width: 768px) {
            .app-header-inner {
                padding: 0 0.9rem;
            }

            .content-shell {
                padding: 1rem 0.9rem 1.3rem;
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
    <div id="sidebar-backdrop" class="sidebar-backdrop"></div>
    <div class="app-layout">
        <aside id="main-navigation" class="app-sidebar" aria-label="Navegacion principal">
            <nav aria-label="Sidebar navigation">
                <section>
                    <div role="group" aria-labelledby="group-label-account">
                        <h3 id="group-label-account" class="sidebar-title">Cuenta</h3>
                        <ul class="sidebar-nav">
                            <li>
                                <a class="sidebar-link" href="{{ route('profile.edit') }}" data-tooltip="Tu nombre y contraseña" data-side="right" @if(request()->is('monitor/profile')) aria-current="page" @endif>
                                    <i data-lucide="circle-user"></i>
                                    <span>Mi perfil</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </section>
                <section>
                    @php($canMonitor = user_can('monitor.view'))
                    @php($canLogs = user_can('logs.view'))
                    @php($canFailed = user_can('failed.view'))
                    @php($canSettings = user_can('settings.manage'))
                    @php($canMappings = user_can('mappings.manage'))
                    @php($canNotifications = user_can('notifications.manage'))
                    @php($canTemplates = user_can('templates.manage'))
                    @php($canWhatsapp = user_can('whatsapp.manage'))
                    @php($canFilters = user_can('filters.manage'))
                    @php($canAlerts = user_can('alerts.manage'))
                    @php($canUsers = user_can('users.manage'))
                    <div role="group" aria-labelledby="group-label-monitor">
                        <h3 id="group-label-monitor" class="sidebar-title">Monitoreo</h3>
                        <ul class="sidebar-nav">
                            @if($canMonitor)
                                <li>
                                <a class="sidebar-link" href="{{ url('/monitor') }}" data-tooltip="Tablero general de monitoreo" data-side="right" @if(request()->is('monitor')) aria-current="page" @endif>
                                        <i data-lucide="layout-dashboard"></i>
                                        <span>Tablero</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="sidebar-link" href="{{ url('/monitor/manual') }}" data-tooltip="Manual Bitrix24 y Botmaker" data-side="right" @if(request()->is('monitor/manual')) aria-current="page" @endif>
                                        <i data-lucide="book-open"></i>
                                        <span>Manual de integración</span>
                                    </a>
                                </li>
                            @endif
                            @if($canLogs)
                                <li>
                                <a class="sidebar-link" href="{{ url('/monitor/logs') }}" data-tooltip="Listado y exportación de webhooks" data-side="right" @if(request()->is('monitor/logs*')) aria-current="page" @endif>
                                        <i data-lucide="list-collapse"></i>
                                        <span>Registros de Webhooks</span>
                                    </a>
                                </li>
                            @endif
                            @if($canFailed)
                                <li>
                                <a class="sidebar-link" href="{{ url('/monitor/failed') }}" data-tooltip="Reintentos y resolución manual" data-side="right" @if(request()->is('monitor/failed*')) aria-current="page" @endif>
                                        <i data-lucide="shield-alert"></i>
                                        <span>Webhooks Fallidos</span>
                                    </a>
                                </li>
                            @endif
                            @if($canSettings)
                                <li>
                                    <a class="sidebar-link" href="{{ url('/monitor/settings') }}" data-tooltip="Configuración técnica de integraciones" data-side="right" @if(request()->is('monitor/settings*')) aria-current="page" @endif>
                                        <i data-lucide="settings"></i>
                                        <span>Configuración</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="sidebar-link" href="{{ route('integration-tests.panel') }}" data-tooltip="Pruebas Bitrix, Botmaker y JSON" data-side="right" @if(request()->is('monitor/integration-tests') || request()->is('monitor/integration-probes*')) aria-current="page" @endif>
                                        <i data-lucide="flask-conical"></i>
                                        <span>Pruebas de integración</span>
                                    </a>
                                </li>
                            @endif
                            @if($canMappings)
                                <li>
                                    <a class="sidebar-link" href="{{ url('/monitor/mappings') }}" data-tooltip="Mapeo dinámico de campos" data-side="right" @if(request()->is('monitor/mappings*')) aria-current="page" @endif>
                                        <i data-lucide="git-compare-arrows"></i>
                                        <span>Mapeo de campos</span>
                                    </a>
                                </li>
                            @endif
                            @if($canNotifications)
                                <li>
                                    <a class="sidebar-link" href="{{ url('/monitor/notifications') }}" data-tooltip="Reglas de mensajes de salida" data-side="right" @if(request()->is('monitor/notifications*')) aria-current="page" @endif>
                                        <i data-lucide="bell-ring"></i>
                                        <span>Reglas de notificación</span>
                                    </a>
                                </li>
                            @endif
                            @if($canTemplates)
                                <li>
                                    <a class="sidebar-link" href="{{ url('/monitor/templates') }}" data-tooltip="Plantillas reutilizables de mensajes" data-side="right" @if(request()->is('monitor/templates*')) aria-current="page" @endif>
                                        <i data-lucide="message-square-text"></i>
                                        <span>Plantillas</span>
                                    </a>
                                </li>
                            @endif
                            @if($canWhatsapp)
                                <li>
                                    <a class="sidebar-link" href="{{ url('/monitor/whatsapp-numbers') }}" data-tooltip="Gestión de números y canales WhatsApp" data-side="right" @if(request()->is('monitor/whatsapp-numbers*')) aria-current="page" @endif>
                                        <i data-lucide="phone-call"></i>
                                        <span>Números WhatsApp</span>
                                    </a>
                                </li>
                            @endif
                            @if($canFilters)
                                <li>
                                    <a class="sidebar-link" href="{{ url('/monitor/event-filters') }}" data-tooltip="Filtros de entrada por evento" data-side="right" @if(request()->is('monitor/event-filters*')) aria-current="page" @endif>
                                        <i data-lucide="filter"></i>
                                        <span>Filtros de eventos</span>
                                    </a>
                                </li>
                            @endif
                            @if($canAlerts)
                                <li>
                                    <a class="sidebar-link" href="{{ url('/monitor/alerts') }}" data-tooltip="Alertas y notificaciones por correo" data-side="right" @if(request()->is('monitor/alerts*')) aria-current="page" @endif>
                                        <i data-lucide="mail-warning"></i>
                                        <span>Alertas por correo</span>
                                    </a>
                                </li>
                            @endif
                            @if($canUsers)
                                <li>
                                    <a class="sidebar-link" href="{{ url('/monitor/access-control') }}" data-tooltip="Usuarios, roles y permisos dinámicos" data-side="right" @if(request()->is('monitor/access-control*')) aria-current="page" @endif>
                                        <i data-lucide="users-round"></i>
                                        <span>Usuarios, Roles y Permisos</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </section>
            </nav>
        </aside>

        <div class="app-content-wrap">
            <header class="app-header">
                <div class="app-header-inner">
                    <div>
                        <p class="muted" style="font-size: 0.78rem; margin: 0;">Monitor del Middleware</p>
                        <h1 style="font-size: 1rem; margin: 0;">{{ $title ?? 'Panel de Webhooks' }}</h1>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        @auth
                            <a class="muted" href="{{ route('profile.edit') }}" style="font-size: 0.84rem; text-decoration: none; max-width: 12rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="Mi perfil">{{ auth()->user()->name }}</a>
                            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                                @csrf
                                <button class="btn" type="submit">Salir</button>
                            </form>
                        @endauth
                        <button id="menu-toggle-btn" class="btn menu-toggle-btn" type="button" aria-label="Abrir menu lateral">
                            <i data-lucide="menu"></i>
                        </button>
                        <button id="theme-toggle" class="btn" type="button">
                            <span style="display: inline-flex; align-items: center; gap: 0.45rem;">
                                <i id="theme-toggle-icon" data-lucide="moon"></i>
                                <span id="theme-toggle-text">Modo claro</span>
                            </span>
                        </button>
                    </div>
                </div>
            </header>

            <main class="content-shell">
                {{ $slot }}
            </main>
            @include('partials.global-footer')
        </div>
    </div>

    @livewireScripts
    <script>
        (function () {
            const root = document.documentElement;
            const btn = document.getElementById('theme-toggle');
            const btnText = document.getElementById('theme-toggle-text');
            const btnIcon = document.getElementById('theme-toggle-icon');
            const savedTheme = localStorage.getItem('ui-theme');
            const initialTheme = savedTheme || 'dark';
            let lucideRenderTimer = null;

            root.setAttribute('data-theme', initialTheme);

            const renderIcons = () => {
                if (window.lucide) window.lucide.createIcons();
            };
            const scheduleRenderIcons = () => {
                if (lucideRenderTimer) {
                    window.clearTimeout(lucideRenderTimer);
                }
                lucideRenderTimer = window.setTimeout(renderIcons, 30);
            };

            const refreshThemeButton = () => {
                const isDark = root.getAttribute('data-theme') === 'dark';
                if (btnText) btnText.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
                if (btnIcon) btnIcon.setAttribute('data-lucide', isDark ? 'sun' : 'moon');
                renderIcons();
            };

            refreshThemeButton();
            renderIcons();

            if (btn) {
                btn.addEventListener('click', () => {
                    const nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                    root.setAttribute('data-theme', nextTheme);
                    localStorage.setItem('ui-theme', nextTheme);
                    refreshThemeButton();
                });
            }

            const menuButton = document.getElementById('menu-toggle-btn');
            const sidebarBackdrop = document.getElementById('sidebar-backdrop');
            const closeSidebar = () => document.body.classList.remove('sidebar-open');
            const toggleSidebar = () => document.body.classList.toggle('sidebar-open');

            if (menuButton) {
                menuButton.addEventListener('click', toggleSidebar);
            }

            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', closeSidebar);
            }

            window.addEventListener('resize', () => {
                if (window.innerWidth > 1024) closeSidebar();
            });

            // Livewire puede reconstruir nodos y borrar SVGs de Lucide.
            document.addEventListener('livewire:init', scheduleRenderIcons);
            document.addEventListener('livewire:initialized', scheduleRenderIcons);
            document.addEventListener('livewire:navigated', scheduleRenderIcons);
            document.addEventListener('livewire:update', scheduleRenderIcons);

            // Respaldo: detecta cambios en el DOM y vuelve a pintar iconos.
            const observer = new MutationObserver(() => {
                scheduleRenderIcons();
            });
            observer.observe(document.body, { childList: true, subtree: true });
        })();
    </script>
</body>
</html>
