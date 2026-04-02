<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class EnvConfigService
{
    /**
     * @param array<int, string> $keys
     * @return array<string, string>
     */
    public function getMany(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $value = env($key);
            $values[$key] = is_scalar($value) ? (string) $value : '';
        }

        return $values;
    }

    /**
     * @param array<string, string> $values
     */
    public function setMany(array $values): void
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            throw new RuntimeException('No se encontro el archivo .env');
        }

        $content = (string) file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $escaped = $this->escapeValue($value);
            $pattern = "/^{$key}=.*$/m";
            $line = "{$key}={$escaped}";

            if (preg_match($pattern, $content) === 1) {
                $content = (string) preg_replace($pattern, $line, $content);
                continue;
            }

            $content = rtrim($content) . PHP_EOL . $line . PHP_EOL;
        }

        file_put_contents($envPath, $content);
    }

    private function escapeValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s/', $value) === 1 || str_contains($value, '#')) {
            return '"' . addcslashes($value, '"') . '"';
        }

        return $value;
    }
}
