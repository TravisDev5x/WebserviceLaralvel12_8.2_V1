<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Monitor de Webhooks' }}</title>
    {{-- Tema antes del primer pintado: evita flash al recargar o cambiar de módulo --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('ui-theme') || 'dark';
                var h = document.documentElement;
                h.setAttribute('data-theme', t);
                h.style.colorScheme = t === 'light' ? 'light' : 'dark';
            } catch (e) {}
        })();
    </script>
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
            width: 100%;
            box-sizing: border-box;
            padding: 0 1.4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .sidebar-account-menu {
            position: relative;
            z-index: 1;
        }

        .sidebar-account-menu:hover {
            z-index: 100;
        }

        .sidebar-account-menu .sidebar-link {
            width: 100%;
        }

        .sidebar-account-menu:focus-within {
            z-index: 100;
        }

        .sidebar-account-menu:focus-within .sidebar-link {
            outline: 2px solid var(--app-border);
            outline-offset: 2px;
        }

        .sidebar-account-menu-chevron {
            width: 0.85rem;
            height: 0.85rem;
            margin-left: auto;
            flex-shrink: 0;
            opacity: 0.65;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .sidebar-account-menu:hover .sidebar-account-menu-chevron,
        .sidebar-account-menu:focus-within .sidebar-account-menu-chevron {
            opacity: 1;
            transform: translateX(2px);
        }

        .sidebar-account-menu-dropdown {
            position: absolute;
            left: calc(100% + 0.35rem);
            bottom: 0;
            padding-left: 0.35rem;
            min-width: min(16.5rem, calc(100vw - var(--sidebar-width) - 2.5rem));
            z-index: 200;
            opacity: 0;
            visibility: hidden;
            transform: translateX(-0.25rem);
            transition: opacity 0.14s ease, transform 0.14s ease, visibility 0.14s;
            pointer-events: none;
        }

        .sidebar-account-menu-dropdown::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 0;
            bottom: 0;
            width: 0.4rem;
        }

        .sidebar-account-menu:hover .sidebar-account-menu-dropdown,
        .sidebar-account-menu:focus-within .sidebar-account-menu-dropdown {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        @media (min-width: 1025px) {
            .sidebar-account-menu:hover .sidebar-account-menu-dropdown,
            .sidebar-account-menu:focus-within .sidebar-account-menu-dropdown {
                transform: translateX(0);
            }
        }

        .account-flyout-card {
            background: var(--app-surface);
            border: 1px solid var(--app-border);
            border-radius: 0.6rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.22);
            padding: 0.75rem 0.85rem;
        }

        html[data-theme="light"] .account-flyout-card {
            box-shadow: 0 0.4rem 1.25rem rgba(15, 23, 42, 0.14);
        }

        .account-flyout-head {
            margin-bottom: 0.65rem;
            padding-bottom: 0.55rem;
            border-bottom: 1px solid var(--app-border);
        }

        .account-flyout-head strong {
            display: block;
            font-size: 0.88rem;
            margin-bottom: 0.2rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .account-flyout-head .account-flyout-email {
            display: block;
            font-size: 0.72rem;
            line-height: 1.35;
            word-break: break-all;
        }

        .account-flyout-actions {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .account-flyout-actions .btn {
            width: 100%;
            justify-content: center;
        }

        .account-flyout-actions form {
            margin: 0;
        }

        @media (max-width: 1024px) {
            .sidebar-account-menu-dropdown {
                left: auto;
                right: 0;
                bottom: 100%;
                top: auto;
                margin-bottom: 0.25rem;
                padding-left: 0;
                padding-bottom: 0.35rem;
                min-width: min(16.5rem, calc(100vw - 2rem));
                transform: translateY(0.35rem);
            }

            .sidebar-account-menu-dropdown::before {
                right: auto;
                left: 0;
                top: 100%;
                bottom: auto;
                width: 100%;
                height: 0.4rem;
            }

            .sidebar-account-menu:hover .sidebar-account-menu-dropdown,
            .sidebar-account-menu:focus-within .sidebar-account-menu-dropdown {
                transform: translateY(0);
            }
        }

        .content-shell {
            width: 100%;
            box-sizing: border-box;
            padding: 1.25rem 1.4rem 1.6rem;
            flex: 1 0 auto;
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

        .app-sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--app-border);
            padding: 0.85rem 0.6rem;
            z-index: 35;
            overflow: visible;
        }

        /* overflow-y:hidden hace que overflow-x pase a auto y recorta el flyout de Mi perfil */
        .app-sidebar nav {
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: visible;
        }

        .sidebar-scroll-area {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-account-block {
            flex-shrink: 0;
            margin-top: auto;
            padding-top: 0.65rem;
            border-top: 1px solid var(--app-border);
            overflow: visible;
            position: relative;
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

        .sidebar-group {
            border: none;
            margin: 0 0 0.35rem;
        }

        .sidebar-group-summary {
            list-style: none;
            cursor: pointer;
            margin: 0.5rem 0.45rem 0.35rem;
            color: var(--app-muted);
            font-size: 0.72rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            font-weight: 600;
        }

        .sidebar-group-summary::-webkit-details-marker {
            display: none;
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
                overflow: visible;
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
    <div id="sidebar-backdrop" class="sidebar-backdrop"></div>
    <div class="app-layout">
        <aside id="main-navigation" class="app-sidebar" aria-label="Navegacion principal">
            <nav aria-label="Sidebar navigation">
                <div class="sidebar-scroll-area">
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
                    @php($isAdmin = auth()->check() && (string) (auth()->user()->role ?? '') === 'admin')
                    @php($isOps = in_array((string) (auth()->user()->role ?? ''), ['admin', 'operator'], true))
                    @php($fp = (int) ($sidebarFailedPendingCount ?? 0))

                    <details class="sidebar-group" open>
                        <summary class="sidebar-group-summary">Monitoreo</summary>
                        <ul class="sidebar-nav">
                            @if($canMonitor)
                                <li><a class="sidebar-link" href="{{ url('/monitor') }}" data-tooltip="Tablero general" data-side="right" @if(request()->is('monitor')) aria-current="page" @endif><i data-lucide="layout-dashboard"></i><span>Tablero</span></a></li>
                                <li><a class="sidebar-link" href="{{ url('/monitor/manual') }}" data-tooltip="Manual de integración" data-side="right" @if(request()->is('monitor/manual')) aria-current="page" @endif><i data-lucide="book-open"></i><span>Manual de integración</span></a></li>
                            @endif
                            @if($canLogs)
                                <li><a class="sidebar-link" href="{{ url('/monitor/logs') }}" data-tooltip="Registros de webhooks" data-side="right" @if(request()->is('monitor/logs*')) aria-current="page" @endif><i data-lucide="list-collapse"></i><span>Registros de Webhooks</span></a></li>
                            @endif
                            @if($canFailed)
                                <li>
                                    <a class="sidebar-link" href="{{ url('/monitor/failed') }}" data-tooltip="Revisar fallos pendientes" data-side="right" @if(request()->is('monitor/failed*')) aria-current="page" @endif>
                                        <i data-lucide="shield-alert"></i>
                                        <span>Webhooks Fallidos</span>
                                        @if($fp > 0)<span class="sidebar-nav-badge" title="Pendientes">{{ $fp > 99 ? '99+' : $fp }}</span>@endif
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </details>

                    @if($canSettings && $isAdmin)
                    <details class="sidebar-group" @if(request()->is('monitor/settings*')) open @endif>
                        <summary class="sidebar-group-summary">Configuración</summary>
                        <ul class="sidebar-nav">
                            <li><a class="sidebar-link" href="{{ url('/monitor/settings') }}" data-tooltip="Centro con tarjetas" data-side="right" @if(request()->is('monitor/settings') && ! request()->is('monitor/settings/*')) aria-current="page" @endif><i data-lucide="layout-grid"></i><span>Centro de configuración</span></a></li>
                            <li>
                                <a class="sidebar-link" href="{{ url('/monitor/settings/botmaker') }}" data-tooltip="API Botmaker" data-side="right" @if(request()->is('monitor/settings/botmaker')) aria-current="page" @endif>
                                    <i data-lucide="message-circle"></i>
                                    <span>Conexión Botmaker</span>
                                    <span class="sidebar-health sidebar-health--{{ ($sidebarHealthBotmaker ?? false) ? 'ok' : 'bad' }}" title="Estado configuración"></span>
                                </a>
                            </li>
                            <li>
                                <a class="sidebar-link" href="{{ url('/monitor/settings/bitrix24') }}" data-tooltip="CRM Bitrix24" data-side="right" @if(request()->is('monitor/settings/bitrix24')) aria-current="page" @endif>
                                    <i data-lucide="contact"></i>
                                    <span>Conexión Bitrix24</span>
                                    <span class="sidebar-health sidebar-health--{{ ($sidebarHealthBitrix ?? false) ? 'ok' : 'bad' }}" title="Estado configuración"></span>
                                </a>
                            </li>
                            <li><a class="sidebar-link" href="{{ route('monitor.tokens') }}" data-tooltip="Tokens y URLs" data-side="right" @if(request()->is('monitor/settings/tokens')) aria-current="page" @endif><i data-lucide="key"></i><span>Webhooks autorizados</span></a></li>
                            <li><a class="sidebar-link" href="{{ url('/monitor/settings/retry') }}" data-tooltip="Cola y reintentos" data-side="right" @if(request()->is('monitor/settings/retry')) aria-current="page" @endif><i data-lucide="timer"></i><span>Reintentos</span></a></li>
                            <li><a class="sidebar-link" href="{{ route('integration-tests.panel') }}" data-tooltip="Pruebas y simulación" data-side="right" @if(request()->is('monitor/settings/test') || request()->is('monitor/integration-probes*')) aria-current="page" @endif><i data-lucide="flask-conical"></i><span>Pruebas de integración</span></a></li>
                        </ul>
                    </details>
                    @elseif($canSettings && ! $isAdmin)
                    <details class="sidebar-group" open>
                        <summary class="sidebar-group-summary">Configuración</summary>
                        <ul class="sidebar-nav">
                            <li><a class="sidebar-link" href="{{ url('/monitor/settings') }}" data-side="right" @if(request()->is('monitor/settings')) aria-current="page" @endif><i data-lucide="layout-grid"></i><span>Centro de configuración</span></a></li>
                        </ul>
                    </details>
                    @endif

                    @if($isOps && ($canMappings || $canNotifications || $canTemplates || $canFilters || $canWhatsapp))
                    <details class="sidebar-group" @if(request()->is('monitor/mappings*') || request()->is('monitor/notifications*') || request()->is('monitor/templates*') || request()->is('monitor/event-filters*') || request()->is('monitor/whatsapp-numbers*')) open @endif>
                        <summary class="sidebar-group-summary">Automatización</summary>
                        <ul class="sidebar-nav">
                            @if($canMappings)<li><a class="sidebar-link" href="{{ url('/monitor/mappings') }}" data-side="right" @if(request()->is('monitor/mappings*')) aria-current="page" @endif><i data-lucide="git-compare-arrows"></i><span>Mapeo de campos</span></a></li>@endif
                            @if($canNotifications)<li><a class="sidebar-link" href="{{ url('/monitor/notifications') }}" data-side="right" @if(request()->is('monitor/notifications*')) aria-current="page" @endif><i data-lucide="bell-ring"></i><span>Reglas de notificación</span></a></li>@endif
                            @if($canTemplates)<li><a class="sidebar-link" href="{{ url('/monitor/templates') }}" data-side="right" @if(request()->is('monitor/templates*')) aria-current="page" @endif><i data-lucide="message-square-text"></i><span>Plantillas</span></a></li>@endif
                            @if($canFilters)<li><a class="sidebar-link" href="{{ url('/monitor/event-filters') }}" data-side="right" @if(request()->is('monitor/event-filters*')) aria-current="page" @endif><i data-lucide="filter"></i><span>Filtros de eventos</span></a></li>@endif
                            @if($canWhatsapp)<li><a class="sidebar-link" href="{{ url('/monitor/whatsapp-numbers') }}" data-side="right" @if(request()->is('monitor/whatsapp-numbers*')) aria-current="page" @endif><i data-lucide="phone-call"></i><span>Números WhatsApp</span></a></li>@endif
                        </ul>
                    </details>
                    @endif

                    @if(($canAlerts && $isOps) || $canUsers)
                    <details class="sidebar-group" @if(request()->is('monitor/alerts*') || request()->is('monitor/access-control*') || request()->is('monitor/users*')) open @endif>
                        <summary class="sidebar-group-summary">Sistema</summary>
                        <ul class="sidebar-nav">
                            @if($canAlerts && $isOps)<li><a class="sidebar-link" href="{{ url('/monitor/alerts') }}" data-side="right" @if(request()->is('monitor/alerts*')) aria-current="page" @endif><i data-lucide="mail-warning"></i><span>Alertas por correo</span></a></li>@endif
                            @if($canUsers)
                                <li><a class="sidebar-link" href="{{ url('/monitor/access-control') }}" data-side="right" @if(request()->is('monitor/access-control*')) aria-current="page" @endif><i data-lucide="users-round"></i><span>Usuarios, roles y permisos</span></a></li>
                                <li><a class="sidebar-link" href="{{ url('/monitor/users') }}" data-side="right" @if(request()->is('monitor/users*')) aria-current="page" @endif><i data-lucide="user-cog"></i><span>Usuarios (lista rápida)</span></a></li>
                            @endif
                        </ul>
                    </details>
                    @endif
                </section>
                </div>
                <div class="sidebar-account-block">
                    <div role="group" aria-labelledby="group-label-account">
                        <h3 id="group-label-account" class="sidebar-title">Cuenta</h3>
                        <ul class="sidebar-nav">
                            <li>
                                <div class="sidebar-account-menu">
                                    <a class="sidebar-link" href="{{ route('profile.edit') }}" id="sidebar-account-menu-trigger" aria-haspopup="true" aria-controls="sidebar-account-flyout" @if(request()->is('monitor/profile')) aria-current="page" @endif>
                                        <i data-lucide="circle-user"></i>
                                        <span>Mi perfil</span>
                                        <i data-lucide="chevron-right" class="sidebar-account-menu-chevron" aria-hidden="true"></i>
                                    </a>
                                    <div class="sidebar-account-menu-dropdown" id="sidebar-account-flyout" role="region" aria-label="Acciones de cuenta">
                                        <div class="account-flyout-card">
                                            <div class="account-flyout-head">
                                                <strong id="sidebar-account-flyout-title">{{ auth()->user()->name }}</strong>
                                                <span class="muted account-flyout-email">{{ auth()->user()->email }}</span>
                                            </div>
                                            <div class="account-flyout-actions" role="group" aria-labelledby="sidebar-account-flyout-title">
                                                <a class="btn btn-primary" href="{{ route('profile.edit') }}">Ir a mi perfil</a>
                                                <form method="POST" action="{{ route('logout') }}">
                                                    @csrf
                                                    <button class="btn" type="submit">Cerrar sesión</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
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
                        <button id="menu-toggle-btn" class="btn menu-toggle-btn" type="button" aria-label="Abrir menu lateral">
                            <i data-lucide="menu"></i>
                        </button>
                        <button id="theme-toggle" class="btn" type="button" aria-label="Cambiar tema">
                            <i id="theme-toggle-icon" data-lucide="moon"></i>
                        </button>
                    </div>
                </div>
            </header>

            <main class="content-shell">
                @if(session('error'))
                    <div class="card card-pad" style="margin-bottom:.75rem;border-left:3px solid #dc2626;background:var(--app-row);" role="alert">
                        <p style="margin:0;">{{ session('error') }}</p>
                    </div>
                @endif
                @if(session('success'))
                    <div class="card card-pad" style="margin-bottom:.75rem;border-left:3px solid #22c55e;background:var(--app-row);" role="status">
                        <p style="margin:0;">{{ session('success') }}</p>
                    </div>
                @endif
                @if(! empty($breadcrumbs ?? []))
                    <x-breadcrumb :items="$breadcrumbs" />
                @endif
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
            const btnIcon = document.getElementById('theme-toggle-icon');
            let lucideDebounceTimer = null;

            const renderIcons = () => {
                if (window.lucide) window.lucide.createIcons();
            };

            const scheduleRenderIconsDebounced = () => {
                if (lucideDebounceTimer) window.clearTimeout(lucideDebounceTimer);
                lucideDebounceTimer = window.setTimeout(renderIcons, 120);
            };

            const refreshThemeButton = () => {
                const isDark = root.getAttribute('data-theme') === 'dark';
                if (btn) btn.setAttribute('aria-label', isDark ? 'Activar modo claro' : 'Activar modo oscuro');
                if (btnIcon) btnIcon.setAttribute('data-lucide', isDark ? 'sun' : 'moon');
                renderIcons();
            };

            function bindAppShell() {
                refreshThemeButton();
                renderIcons();

                if (btn) {
                    btn.addEventListener('click', () => {
                        const nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                        root.setAttribute('data-theme', nextTheme);
                        root.style.colorScheme = nextTheme === 'light' ? 'light' : 'dark';
                        try {
                            localStorage.setItem('ui-theme', nextTheme);
                        } catch (e) {}
                        refreshThemeButton();
                    });
                }

                const menuButton = document.getElementById('menu-toggle-btn');
                const sidebarBackdrop = document.getElementById('sidebar-backdrop');
                const closeSidebar = () => document.body.classList.remove('sidebar-open');
                const toggleSidebar = () => document.body.classList.toggle('sidebar-open');

                if (menuButton) menuButton.addEventListener('click', toggleSidebar);
                if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', closeSidebar);

                window.addEventListener('resize', () => {
                    if (window.innerWidth > 1024) closeSidebar();
                });

                document.addEventListener('livewire:init', scheduleRenderIconsDebounced);
                document.addEventListener('livewire:initialized', scheduleRenderIconsDebounced);
                document.addEventListener('livewire:navigated', scheduleRenderIconsDebounced);
                document.addEventListener('livewire:update', scheduleRenderIconsDebounced);
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
