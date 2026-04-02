<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'services.botmaker.api_url' => ['botmaker.api_url', 'string'],
            'services.botmaker.api_token' => ['botmaker.api_token', 'string'],
            'services.botmaker.webhook_secret' => ['botmaker.webhook_secret', 'string'],
            'services.bitrix24.webhook_url' => ['bitrix24.webhook_url', 'string'],
            'services.bitrix24.webhook_secret' => ['bitrix24.webhook_secret', 'string'],
            'retry.max_attempts' => ['retry.max_attempts', 'integer', 5],
            'retry.backoff_schedule' => ['retry.backoff_schedule', 'json', [30, 60, 300, 900, 3600]],
            'retry.http_timeout' => ['retry.http_timeout', 'integer', 15],
        ];

        foreach ($map as $configKey => $target) {
            [$settingKey, $type] = $target;
            $default = $target[2] ?? null;
            $value = config($configKey, $default);
            if ($value === null || $value === '') {
                continue;
            }
            Setting::set($settingKey, $value, $type);
        }

        $integrations = config('integrations.botmaker_to_bitrix', []);
        if (is_array($integrations)) {
            Setting::set('integrations.botmaker_to_bitrix', $integrations, 'json');
        }
    }
}
