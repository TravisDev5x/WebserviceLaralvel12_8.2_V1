<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AlertRule;
use App\Models\AuthorizedToken;
use App\Models\FieldMapping;
use App\Models\MessageTemplate;
use App\Models\NotificationRule;
use App\Models\WhatsappNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class SettingsHub extends Component
{
    public function render(): View
    {
        $apiUrl = (string) config_dynamic('botmaker.api_url', config('services.botmaker.api_url', ''));
        $apiToken = (string) config_dynamic('botmaker.api_token', config('services.botmaker.api_token', ''));
        $botmakerFromDb = AuthorizedToken::getPrimaryBotmakerApiToken();
        $botmakerOk = $apiUrl !== '' && (trim($apiToken) !== '' || ($botmakerFromDb !== null && $botmakerFromDb !== ''));

        $bitrixUrl = (string) config_dynamic('bitrix24.webhook_url', config('services.bitrix24.webhook_url', ''));
        $bitrixFromToken = Schema::hasTable('authorized_tokens') ? AuthorizedToken::getWebhookUrl('bitrix24') : null;
        $bitrixOk = ($bitrixFromToken !== null && $bitrixFromToken !== '') || (trim($bitrixUrl) !== '' && ! str_contains($bitrixUrl, 'dominio.bitrix24'));

        $activeTokens = 0;
        if (Schema::hasTable('authorized_tokens')) {
            $activeTokens = (int) AuthorizedToken::query()->where('is_active', true)->count();
        }

        return view('livewire.settings-hub', [
            'botmakerConfigured' => $botmakerOk,
            'bitrixConfigured' => $bitrixOk,
            'activeTokensCount' => $activeTokens,
            'fieldMappingsCount' => FieldMapping::query()->count(),
            'notificationRulesActive' => NotificationRule::query()->where('is_active', true)->count(),
            'templatesCount' => MessageTemplate::query()->count(),
            'whatsappActive' => WhatsappNumber::query()->where('is_active', true)->count(),
            'alertsActive' => AlertRule::query()->where('is_active', true)->count(),
        ])->layout('layouts.app', [
            'title' => 'Centro de configuración',
        ]);
    }
}
