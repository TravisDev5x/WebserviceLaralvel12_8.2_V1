<?php

namespace App\Providers;

use App\Models\AuthorizedToken;
use App\Models\FailedWebhook;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
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

            $bmUrl = AuthorizedToken::resolvedBotmakerApiUrl();
            $bmTok = AuthorizedToken::resolvedBotmakerApiToken();
            $healthBotmaker = $bmUrl !== '' && $bmTok !== '';

            $bxUrl = AuthorizedToken::resolvedBitrix24WebhookUrl();
            $healthBitrix = $bxUrl !== '' && ! str_contains($bxUrl, 'dominio.bitrix24');

            $view->with([
                'breadcrumbs' => monitor_breadcrumbs(),
                'sidebarFailedPendingCount' => $failedPending,
                'sidebarHealthBotmaker' => $healthBotmaker,
                'sidebarHealthBitrix' => $healthBitrix,
            ]);
        });
    }
}
