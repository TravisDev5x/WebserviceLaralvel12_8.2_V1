<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRule extends Model
{
    protected $fillable = [
        'name',
        'event_type',
        'condition_field',
        'condition_operator',
        'condition_value',
        'message_template_id',
        'message_template',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    /**
     * @param array<string,mixed> $leadData
     */
    public function matchesCondition(array $leadData): bool
    {
        $field = (string) ($this->condition_field ?? '');
        if ($field === '') {
            return true;
        }
        $value = (string) data_get($leadData, $field, '');
        $expected = (string) ($this->condition_value ?? '');

        return match ((string) $this->condition_operator) {
            'not_equals' => $value !== $expected,
            'contains' => str_contains(mb_strtoupper($value), mb_strtoupper($expected)),
            'changed_to' => $value === $expected,
            default => $value === $expected,
        };
    }

    /**
     * @param array<string,mixed> $leadData
     */
    public function renderMessage(array $leadData): string
    {
        $body = $this->message_template_id && $this->template
            ? (string) $this->template->body
            : (string) ($this->message_template ?? '');

        $vars = [
            '{nombre}' => (string) data_get($leadData, 'nombre', data_get($leadData, 'name', '')),
            '{apellido}' => (string) data_get($leadData, 'apellido', data_get($leadData, 'last_name', '')),
            '{telefono}' => (string) data_get($leadData, 'telefono', data_get($leadData, 'phone', '')),
            '{estatus}' => (string) data_get($leadData, 'estatus', data_get($leadData, 'status', '')),
            '{lead_id}' => (string) data_get($leadData, 'lead_id', ''),
            '{agente}' => (string) data_get($leadData, 'agente', ''),
            '{fecha}' => now()->format('Y-m-d H:i:s'),
        ];

        return strtr($body, $vars);
    }
}
