<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AuthorizedToken;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AuthorizedTokensFromEnvSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('authorized_tokens')) {
            return;
        }

        $this->seedBitrixFromEnv();
        $this->seedBotmakerWebhookFromEnv();
        $this->seedBotmakerApiFromEnv();
    }

    private function seedBitrixFromEnv(): void
    {
        if (AuthorizedToken::query()->where('platform', 'bitrix24')->exists()) {
            return;
        }

        $secret = trim((string) env('BITRIX24_WEBHOOK_SECRET', ''));
        if ($secret === '' || $secret === '__PENDIENTE__') {
            $secret = '';
        }

        $url = trim((string) env('BITRIX24_WEBHOOK_URL', ''));
        if ($url === '__PENDIENTE__' || str_contains($url, '__PENDIENTE__')) {
            $url = '';
        }

        if ($url !== '') {
            AuthorizedToken::query()->create([
                'platform' => 'bitrix24',
                'label' => 'Token inicial (migrado de .env) — URL entrante',
                'token' => '',
                'webhook_url' => $url,
                'direction' => AuthorizedToken::DIRECTION_INCOMING,
                'is_active' => true,
                'notes' => 'Migrado desde BITRIX24_WEBHOOK_URL',
            ]);
        }

        if ($secret !== '') {
            AuthorizedToken::query()->create([
                'platform' => 'bitrix24',
                'label' => 'Token inicial (migrado de .env) — saliente',
                'token' => $secret,
                'webhook_url' => null,
                'direction' => AuthorizedToken::DIRECTION_OUTGOING,
                'is_active' => true,
                'notes' => 'Migrado desde BITRIX24_WEBHOOK_SECRET',
            ]);
        }
    }

    private function seedBotmakerWebhookFromEnv(): void
    {
        if (AuthorizedToken::query()->where('platform', 'botmaker')->where('direction', AuthorizedToken::DIRECTION_OUTGOING)->exists()) {
            return;
        }

        $secret = trim((string) env('BOTMAKER_WEBHOOK_SECRET', ''));
        if ($secret === '' || $secret === '__PENDIENTE__') {
            return;
        }

        AuthorizedToken::query()->create([
            'platform' => 'botmaker',
            'label' => 'Token inicial (migrado de .env) — webhook',
            'token' => $secret,
            'webhook_url' => null,
            'direction' => AuthorizedToken::DIRECTION_OUTGOING,
            'is_active' => true,
            'notes' => 'Migrado desde BOTMAKER_WEBHOOK_SECRET',
        ]);
    }

    private function seedBotmakerApiFromEnv(): void
    {
        if (AuthorizedToken::query()->where('platform', 'botmaker')->where('direction', AuthorizedToken::DIRECTION_INCOMING)->exists()) {
            return;
        }

        $token = trim((string) env('BOTMAKER_API_TOKEN', ''));
        if ($token === '' || $token === '__PENDIENTE__') {
            return;
        }

        AuthorizedToken::query()->create([
            'platform' => 'botmaker',
            'label' => 'Token inicial (migrado de .env) — API',
            'token' => $token,
            'webhook_url' => null,
            'direction' => AuthorizedToken::DIRECTION_INCOMING,
            'is_active' => true,
            'notes' => 'Migrado desde BOTMAKER_API_TOKEN',
        ]);
    }
}
