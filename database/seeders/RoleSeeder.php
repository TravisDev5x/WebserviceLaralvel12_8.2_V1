<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $all = [
            'monitor.view',
            'logs.view',
            'failed.view',
            'failed.manage',
            'settings.manage',
            'mappings.manage',
            'notifications.manage',
            'templates.manage',
            'whatsapp.manage',
            'filters.manage',
            'alerts.manage',
            'users.manage',
        ];

        Role::query()->updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrador',
                'description' => 'Control total del sistema',
                'permissions' => ['*'],
                'is_active' => true,
            ],
        );

        Role::query()->updateOrCreate(
            ['slug' => 'operator'],
            [
                'name' => 'Operador',
                'description' => 'Operación diaria y configuración funcional',
                'permissions' => array_values(array_filter($all, static fn (string $p): bool => $p !== 'users.manage')),
                'is_active' => true,
            ],
        );

        Role::query()->updateOrCreate(
            ['slug' => 'viewer'],
            [
                'name' => 'Visualizador',
                'description' => 'Solo lectura de monitoreo',
                'permissions' => ['monitor.view', 'logs.view', 'failed.view'],
                'is_active' => true,
            ],
        );
    }
}
