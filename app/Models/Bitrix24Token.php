<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitrix24Token extends Model
{
    protected $table = 'bitrix24_tokens';

    protected $fillable = [
        'domain',
        'access_token',
        'refresh_token',
        'expires_at',
        'application_token',
        'client_id',
        'client_secret',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'client_secret' => 'encrypted',
        ];
    }

    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Returns the active token record for the configured domain (or the most recent one).
     */
    public static function getActive(): ?self
    {
        $domain = config_dynamic('bitrix24.domain', config('services.bitrix24.domain'));

        if ($domain) {
            return self::query()
                ->where('domain', $domain)
                ->latest('updated_at')
                ->first();
        }

        return self::query()
            ->latest('updated_at')
            ->first();
    }
}
