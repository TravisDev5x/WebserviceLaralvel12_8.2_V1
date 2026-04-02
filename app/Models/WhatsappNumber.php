<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappNumber extends Model
{
    protected $fillable = ['phone_number', 'label', 'platform_id', 'is_active', 'is_default'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public static function getDefault(): ?self
    {
        return self::query()->active()->default()->first()
            ?? self::query()->active()->first();
    }
}
