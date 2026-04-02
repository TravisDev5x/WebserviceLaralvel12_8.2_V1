<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    protected $fillable = [
        'name',
        'condition_type',
        'threshold',
        'time_window_minutes',
        'notify_email',
        'is_active',
        'last_triggered_at',
        'cooldown_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
            'threshold' => 'integer',
            'time_window_minutes' => 'integer',
            'cooldown_minutes' => 'integer',
        ];
    }
}
