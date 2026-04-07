<?php

namespace App\Providers;

use App\Models\AuthorizedToken;
use App\Models\FailedWebhook;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.basecoat');
        Paginator::defaultSimpleView('vendor.pagination.simple-basecoat');

        View::composer('layouts.app', function (\Illuminate\View\View $view): void {
            $failedPending = Cache::remember('sidebar_failed_pending_v1', 60, static function (): int {
                return (int) FailedWebhook::query()->where('status', FailedWebhook::STATUS_PENDING)->count();
            });

            $bmUrl = trim((string) config_dynamic('botmaker.api_url', config('services.botmaker.api_url', '')));
            $bmTok = trim((string) config_dynamic('botmaker.api_token', config('services.botmaker.api_token', '')));
            $bmDb = AuthorizedToken::getPrimaryBotmakerApiToken();
            $healthBotmaker = $bmUrl !== '' && ($bmTok !== '' || (is_string($bmDb) && trim($bmDb) !== ''));

            $bxSetting = trim((string) config_dynamic('bitrix24.webhook_url', config('services.bitrix24.webhook_url', '')));
            $bxDb = Schema::hasTable('authorized_tokens') ? AuthorizedToken::getWebhookUrl('bitrix24') : null;
            $healthBitrix = (is_string($bxDb) && $bxDb !== '')
                || ($bxSetting !== '' && ! str_contains($bxSetting, 'dominio.bitrix24'));

            $view->with([
                'breadcrumbs' => monitor_breadcrumbs(),
                'sidebarFailedPendingCount' => $failedPending,
                'sidebarHealthBotmaker' => $healthBotmaker,
                'sidebarHealthBitrix' => $healthBitrix,
            ]);
        });
    }
}
