<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['name', 'slug', 'category', 'body', 'variables_available', 'is_active'];

    protected function casts(): array
    {
        return [
            'variables_available' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function render(array $data): string
    {
        $body = (string) $this->body;
        foreach ($data as $key => $value) {
            $body = str_replace('{'.$key.'}', (string) $value, $body);
        }

        return $body;
    }
}
