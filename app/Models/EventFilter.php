<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventFilter extends Model
{
    protected $fillable = [
        'platform',
        'event_type',
        'filter_field',
        'filter_operator',
        'filter_value',
        'action',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function evaluate(array $payload): bool
    {
        if ($this->event_type !== '' && $this->event_type !== '*' && ((string) data_get($payload, 'event', data_get($payload, 'type', ''))) !== (string) $this->event_type) {
            return false;
        }

        $fieldValue = (string) data_get($payload, (string) $this->filter_field, '');
        $filterValue = (string) ($this->filter_value ?? '');

        return match ((string) $this->filter_operator) {
            'not_equals' => $fieldValue !== $filterValue,
            'contains' => str_contains(mb_strtoupper($fieldValue), mb_strtoupper($filterValue)),
            'not_contains' => ! str_contains(mb_strtoupper($fieldValue), mb_strtoupper($filterValue)),
            'is_empty' => trim($fieldValue) === '',
            'is_not_empty' => trim($fieldValue) !== '',
            default => $fieldValue === $filterValue,
        };
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function shouldProcess(string $platform, array $payload): bool
    {
        $filters = self::query()->forPlatform($platform)->active()->get();
        if ($filters->isEmpty()) {
            return true;
        }

        foreach ($filters as $filter) {
            if (! $filter->evaluate($payload)) {
                continue;
            }

            return (string) $filter->action !== 'ignore';
        }

        return true;
    }
}
