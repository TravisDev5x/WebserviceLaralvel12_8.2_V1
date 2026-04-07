<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AuthorizedToken extends Model
{
    public const DIRECTION_INCOMING = 'incoming';

    public const DIRECTION_OUTGOING = 'outgoing';

    protected $fillable = [
        'platform',
        'label',
        'token',
        'webhook_url',
        'direction',
        'is_active',
        'last_used_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @var list<string>
     */
    protected $hidden = [
        'token',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $model): void {
            self::forgetCache($model->platform);
        });

        static::deleted(function (self $model): void {
            self::forgetCache($model->platform);
        });
    }

    /**
     * @param  Builder<AuthorizedToken>  $query
     * @return Builder<AuthorizedToken>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<AuthorizedToken>  $query
     * @return Builder<AuthorizedToken>
     */
    public function scopePlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * @param  Builder<AuthorizedToken>  $query
     * @return Builder<AuthorizedToken>
     */
    public function scopeIncoming(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_INCOMING);
    }

    /**
     * @param  Builder<AuthorizedToken>  $query
     * @return Builder<AuthorizedToken>
     */
    public function scopeOutgoing(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_OUTGOING);
    }

    public static function forgetCache(?string $platform = null): void
    {
        $platforms = $platform !== null && $platform !== '' ? [$platform] : ['bitrix24', 'botmaker'];
        foreach ($platforms as $p) {
            Cache::forget(self::cacheKeyOutgoing($p));
            Cache::forget(self::cacheKeyIncomingUrl($p));
        }
        Cache::forget(self::cacheKeyBotmakerApiToken());
    }

    public static function hasActiveForPlatform(string $platform): bool
    {
        if (! Schema::hasTable('authorized_tokens')) {
            return false;
        }

        return self::cachedOutgoingRows($platform)->isNotEmpty();
    }

    public static function isValid(string $platform, string $incomingToken): bool
    {
        if ($incomingToken === '' || ! Schema::hasTable('authorized_tokens')) {
            return false;
        }

        $rows = self::cachedOutgoingRows($platform);
        foreach ($rows as $row) {
            if (hash_equals((string) $row->token, $incomingToken)) {
                self::query()->whereKey($row->id)->update(['last_used_at' => now()]);

                return true;
            }
        }

        return false;
    }

    public static function getWebhookUrl(string $platform): ?string
    {
        if (! Schema::hasTable('authorized_tokens')) {
            return null;
        }

        return Cache::remember(self::cacheKeyIncomingUrl($platform), 60, function () use ($platform): ?string {
            $row = self::query()
                ->active()
                ->platform($platform)
                ->incoming()
                ->whereNotNull('webhook_url')
                ->where('webhook_url', '!=', '')
                ->orderBy('id')
                ->first(['webhook_url']);

            if ($row === null) {
                return null;
            }

            $url = trim((string) $row->webhook_url);

            return $url !== '' ? $url : null;
        });
    }

    public static function getPrimaryBotmakerApiToken(): ?string
    {
        if (! Schema::hasTable('authorized_tokens')) {
            return null;
        }

        return Cache::remember(self::cacheKeyBotmakerApiToken(), 60, function (): ?string {
            $row = self::query()
                ->active()
                ->platform('botmaker')
                ->incoming()
                ->where('token', '!=', '')
                ->orderBy('id')
                ->first(['token']);

            if ($row === null) {
                return null;
            }

            $t = trim((string) $row->token);

            return $t !== '' ? $t : null;
        });
    }

    public static function resolvedBitrix24WebhookUrl(): string
    {
        $fromDb = self::getWebhookUrl('bitrix24');
        if (is_string($fromDb) && $fromDb !== '') {
            return rtrim($fromDb, '/');
        }

        return rtrim((string) config_dynamic('bitrix24.webhook_url', config('services.bitrix24.webhook_url', '')), '/');
    }

    /**
     * @return Collection<int, AuthorizedToken>
     */
    private static function cachedOutgoingRows(string $platform): Collection
    {
        if (! Schema::hasTable('authorized_tokens')) {
            /** @var Collection<int, AuthorizedToken> $empty */
            $empty = new Collection;

            return $empty;
        }

        /** @var Collection<int, AuthorizedToken> */
        return Cache::remember(self::cacheKeyOutgoing($platform), 60, function () use ($platform): Collection {
            return self::query()
                ->active()
                ->platform($platform)
                ->outgoing()
                ->where('token', '!=', '')
                ->orderBy('id')
                ->get(['id', 'token']);
        });
    }

    private static function cacheKeyOutgoing(string $platform): string
    {
        return "authorized_tokens:outgoing:{$platform}";
    }

    private static function cacheKeyIncomingUrl(string $platform): string
    {
        return "authorized_tokens:incoming_url:{$platform}";
    }

    private static function cacheKeyBotmakerApiToken(): string
    {
        return 'authorized_tokens:botmaker_incoming_api_token';
    }
}
