<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'permissions', 'is_active'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = is_array($this->permissions) ? $this->permissions : [];
        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }
}
