<?php

declare(strict_types=1);

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Asegura que el rol operador en BD incluya failed.manage (reintentos en webhooks fallidos).
     */
    public function up(): void
    {
        $role = Role::query()->where('slug', 'operator')->first();
        if ($role === null) {
            return;
        }

        $permissions = is_array($role->permissions) ? $role->permissions : [];
        if (in_array('*', $permissions, true) || in_array('failed.manage', $permissions, true)) {
            return;
        }

        $permissions[] = 'failed.manage';
        $role->update(['permissions' => array_values(array_unique($permissions))]);
    }

    public function down(): void
    {
        $role = Role::query()->where('slug', 'operator')->first();
        if ($role === null) {
            return;
        }

        $permissions = is_array($role->permissions) ? $role->permissions : [];
        $permissions = array_values(array_filter($permissions, static fn (mixed $p): bool => $p !== 'failed.manage'));
        $role->update(['permissions' => $permissions]);
    }
};
