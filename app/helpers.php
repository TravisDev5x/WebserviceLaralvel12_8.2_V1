<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\Setting;
use Illuminate\Support\Str;

if (! function_exists('config_dynamic')) {
    function config_dynamic(string $dotKey, mixed $default = null): mixed
    {
        $dbValue = Setting::get($dotKey, null);
        if ($dbValue !== null && $dbValue !== '') {
            return $dbValue;
        }

        return config($dotKey, $default);
    }
}

if (! function_exists('permissions_catalog')) {
    /**
     * @return array<int, string>
     */
    function permissions_catalog(): array
    {
        return [
            'monitor.view',
            'logs.view',
            'failed.view',
            'settings.manage',
            'mappings.manage',
            'notifications.manage',
            'templates.manage',
            'whatsapp.manage',
            'filters.manage',
            'alerts.manage',
            'users.manage',
        ];
    }
}

if (! function_exists('user_can')) {
    function user_can(string $permission): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $roleSlug = (string) ($user->role ?? 'viewer');
        $role = Role::query()->where('slug', $roleSlug)->where('is_active', true)->first();
        if ($role instanceof Role) {
            return $role->hasPermission($permission);
        }

        return match ($roleSlug) {
            'admin' => true,
            'operator' => $permission !== 'users.manage',
            default => in_array($permission, ['monitor.view', 'logs.view', 'failed.view'], true),
        };
    }
}

if (! function_exists('monitor_breadcrumbs')) {
    /**
     * Migas de pan para rutas /monitor*. El último ítem lleva url null (página actual).
     *
     * @return list<array{label: string, url: string|null}>
     */
    function monitor_breadcrumbs(): array
    {
        if (! request()->is('monitor*')) {
            return [];
        }

        $path = request()->path();

        $trail = [];
        $dashboardUrl = url('/monitor');
        $onDashboard = $path === 'monitor';

        if (! $onDashboard) {
            $trail[] = ['label' => 'Tablero', 'url' => $dashboardUrl];
        }

        if ($path !== 'monitor/settings' && str_starts_with($path, 'monitor/settings/')) {
            $trail[] = ['label' => 'Centro de configuración', 'url' => url('/monitor/settings')];
        }

        $map = [
            'monitor' => ['Tablero'],
            'monitor/manual' => ['Manual de integración'],
            'monitor/profile' => ['Mi perfil'],
            'monitor/logs' => ['Registros de Webhooks'],
            'monitor/failed' => ['Webhooks fallidos'],
            'monitor/settings' => ['Centro de configuración'],
            'monitor/settings/botmaker' => ['Conexión Botmaker'],
            'monitor/settings/bitrix24' => ['Conexión Bitrix24'],
            'monitor/settings/tokens' => ['Webhooks autorizados'],
            'monitor/settings/retry' => ['Reintentos y rendimiento'],
            'monitor/settings/test' => ['Pruebas de integración'],
            'monitor/tokens' => ['Webhooks autorizados'],
            'monitor/integration-tests' => ['Pruebas de integración'],
            'monitor/mappings' => ['Mapeo de campos'],
            'monitor/notifications' => ['Reglas de notificación'],
            'monitor/templates' => ['Plantillas'],
            'monitor/whatsapp-numbers' => ['Números WhatsApp'],
            'monitor/event-filters' => ['Filtros de eventos'],
            'monitor/alerts' => ['Alertas por correo'],
            'monitor/users' => ['Usuarios y roles'],
            'monitor/access-control' => ['Usuarios, roles y permisos'],
            'monitor/users' => ['Usuarios'],
        ];

        if (isset($map[$path])) {
            foreach ($map[$path] as $label) {
                $trail[] = ['label' => $label, 'url' => null];
            }

            return $trail;
        }

        if (preg_match('#^monitor/logs/(\d+)$#', $path, $m)) {
            $trail[] = ['label' => 'Registros de Webhooks', 'url' => url('/monitor/logs')];
            $trail[] = ['label' => 'Detalle #'.$m[1], 'url' => null];

            return $trail;
        }

        $suffix = Str::after($path, 'monitor/');
        if ($suffix !== '' && $suffix !== $path) {
            $trail[] = ['label' => Str::headline(str_replace(['-', '_'], ' ', $suffix)), 'url' => null];

            return $trail;
        }

        if ($path === 'monitor') {
            $trail[] = ['label' => 'Tablero', 'url' => null];
        }

        return $trail;
    }
}
