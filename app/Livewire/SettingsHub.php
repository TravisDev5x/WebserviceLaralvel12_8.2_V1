<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AlertRule;
use App\Models\AuthorizedToken;
use App\Models\FieldMapping;
use App\Models\MessageTemplate;
use App\Models\NotificationRule;
use App\Models\Setting;
use App\Models\WhatsappNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class SettingsHub extends Component
{
    public function render(): View
    {
        $apiUrl = AuthorizedToken::resolvedBotmakerApiUrl();
        $apiToken = AuthorizedToken::resolvedBotmakerApiToken();
        $botmakerOk = $apiUrl !== '' && $apiToken !== '';

        $bitrixUrl = AuthorizedToken::resolvedBitrix24WebhookUrl();
        $bitrixOk = trim($bitrixUrl) !== '' && ! str_contains($bitrixUrl, 'dominio.bitrix24');

        $activeTokens = 0;
        if (Schema::hasTable('authorized_tokens')) {
            $activeTokens = (int) AuthorizedToken::query()->where('is_active', true)->count();
        }

        $settingsReady = Schema::hasTable('settings');

        return view('livewire.settings-hub', [
            'isAdmin' => auth()->check() && (string) (auth()->user()->role ?? '') === 'admin',
            'botmakerConfigured' => $botmakerOk,
            'bitrixConfigured' => $bitrixOk,
            'activeTokensCount' => $activeTokens,
            'fieldMappingsCount' => FieldMapping::query()->count(),
            'notificationRulesActive' => NotificationRule::query()->where('is_active', true)->count(),
            'templatesCount' => MessageTemplate::query()->count(),
            'whatsappActive' => WhatsappNumber::query()->where('is_active', true)->count(),
            'alertsActive' => AlertRule::query()->where('is_active', true)->count(),
            'botmakerUpdatedAt' => $this->humanUpdatedAt($settingsReady ? Setting::query()->where('group', 'botmaker')->max('updated_at') : null),
            'bitrixUpdatedAt' => $this->humanUpdatedAt($settingsReady ? Setting::query()->where('group', 'bitrix24')->max('updated_at') : null),
        ])->layout('layouts.app', [
            'title' => 'Centro de configuración',
        ]);
    }

    private function humanUpdatedAt(mixed $value): string
    {
        if ($value === null) {
            return 'nunca';
        }

        try {
            return Carbon::parse((string) $value)->diffForHumans();
        } catch (\Throwable) {
            return 'nunca';
        }
    }
}
