<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldMapping extends Model
{
    protected $fillable = [
        'source_platform',
        'source_field',
        'source_path',
        'target_platform',
        'target_field',
        'target_path',
        'transform_type',
        'transform_config',
        'is_active',
        'sort_order',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'transform_config' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePlatform($query, string $platform)
    {
        return $query->where('source_platform', $platform);
    }

    public static function getMappings(string $platform)
    {
        return self::query()->platform($platform)->active()->orderBy('sort_order')->get();
    }

    public function applyTransform(mixed $value): mixed
    {
        $type = (string) ($this->transform_type ?? 'none');
        $config = is_array($this->transform_config) ? $this->transform_config : [];

        return match ($type) {
            'uppercase' => is_string($value) ? mb_strtoupper($value) : $value,
            'lowercase' => is_string($value) ? mb_strtolower($value) : $value,
            'trim' => is_string($value) ? trim($value) : $value,
            'date_format' => $this->transformDate($value, (string) ($config['format'] ?? 'Y-m-d')),
            'currency' => $this->transformCurrency($value, (string) ($config['currency'] ?? 'MXN')),
            'catalog' => $this->transformCatalog($value, $config),
            default => $value,
        };
    }

    private function transformDate(mixed $value, string $format): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return (new \DateTimeImmutable($value))->format($format);
        } catch (\Throwable) {
            return null;
        }
    }

    private function transformCurrency(mixed $value, string $currency): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $raw = (string) $value;
        $normalized = str_replace([',', '$', ' '], '', $raw);
        $numeric = preg_replace('/[^\d.]/', '', $normalized) ?? '';
        if ($numeric === '') {
            return null;
        }

        return $numeric.'|'.strtoupper($currency);
    }

    /**
     * @param array<string,mixed> $config
     */
    private function transformCatalog(mixed $value, array $config): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $map = $config['map'] ?? $config;
        if (! is_array($map)) {
            return $value;
        }
        foreach ($map as $label => $mapped) {
            if (mb_strtoupper(trim((string) $label)) === mb_strtoupper(trim($value))) {
                return is_string($mapped) || is_numeric($mapped) ? (string) $mapped : $value;
            }
        }

        return $value;
    }
}
