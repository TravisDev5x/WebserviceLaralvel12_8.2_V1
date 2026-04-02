<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'description'];

    public static function get(string $dotKey, mixed $default = null): mixed
    {
        [$group, $key] = self::splitDotKey($dotKey);
        if ($group === null || $key === null) {
            return $default;
        }

        $cacheKey = self::cacheKey($group, $key);
        $cached = Cache::remember($cacheKey, 60, static function () use ($group, $key) {
            return self::query()->where('group', $group)->where('key', $key)->first();
        });

        if (! $cached instanceof self) {
            return $default;
        }

        return self::castFromType((string) $cached->type, $cached->value);
    }

    public static function set(string $dotKey, mixed $value, string $type = 'string', ?string $description = null): self
    {
        [$group, $key] = self::splitDotKey($dotKey);
        if ($group === null || $key === null) {
            throw new \InvalidArgumentException('Formato inválido, usa group.key');
        }

        $record = self::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => self::castToStorage($value, $type),
                'type' => $type,
                'description' => $description,
            ],
        );

        self::flushCache($group, $key);

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getGroup(string $group): array
    {
        $cacheKey = "settings:group:{$group}";

        return Cache::remember($cacheKey, 60, static function () use ($group): array {
            $rows = self::query()->where('group', $group)->get();
            $output = [];
            foreach ($rows as $row) {
                $output[(string) $row->key] = self::castFromType((string) $row->type, $row->value);
            }

            return $output;
        });
    }

    private static function flushCache(string $group, string $key): void
    {
        Cache::forget(self::cacheKey($group, $key));
        Cache::forget("settings:group:{$group}");
    }

    private static function cacheKey(string $group, string $key): string
    {
        return "settings:{$group}.{$key}";
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private static function splitDotKey(string $dotKey): array
    {
        $parts = explode('.', $dotKey, 2);
        if (count($parts) !== 2) {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }

    private static function castToStorage(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            'boolean' => (string) ((bool) $value ? '1' : '0'),
            'integer' => (string) ((int) $value),
            default => (string) $value,
        };
    }

    private static function castFromType(string $type, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'json' => is_array(json_decode((string) $value, true)) ? json_decode((string) $value, true) : [],
            'boolean' => in_array((string) $value, ['1', 'true', 'TRUE'], true),
            'integer' => (int) $value,
            default => (string) $value,
        };
    }
}
