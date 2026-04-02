<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\Role;

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
