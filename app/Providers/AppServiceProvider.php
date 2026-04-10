<?php

namespace App\Providers;

use App\Models\AuthorizedToken;
use App\Models\FailedWebhook;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        RateLimiter::for('webhooks', fn () => Limit::perMinute(120));

        Paginator::defaultView('vendor.pagination.basecoat');
        Paginator::defaultSimpleView('vendor.pagination.simple-basecoat');

        View::composer('layouts.app', function (\Illuminate\View\View $view): void {
            $breadcrumbs = [];
            try {
                $breadcrumbs = monitor_breadcrumbs();
            } catch (Throwable) {
                // Evita tumbar todo el layout si la petición o helpers fallan.
            }

            $failedPending = 0;
            $healthBotmaker = false;
            $healthBitrix = false;

            try {
                if (Schema::hasTable('failed_webhooks')) {
                    $failedPending = (int) Cache::remember('sidebar_failed_pending_v1', 60, static function (): int {
                        return (int) FailedWebhook::query()->where('status', FailedWebhook::STATUS_PENDING)->count();
                    });
                }

                $bmUrl = AuthorizedToken::resolvedBotmakerApiUrl();
                $bmTok = AuthorizedToken::resolvedBotmakerApiToken();
                $healthBotmaker = $bmUrl !== '' && $bmTok !== '';

                $bxUrl = AuthorizedToken::resolvedBitrix24WebhookUrl();
                $healthBitrix = $bxUrl !== '' && ! str_contains($bxUrl, 'dominio.bitrix24');
            } catch (Throwable $e) {
                Log::warning('layouts.app view composer: '.$e->getMessage(), ['exception' => $e]);
            }

            $view->with([
                'breadcrumbs' => $breadcrumbs,
                'sidebarFailedPendingCount' => $failedPending,
                'sidebarHealthBotmaker' => $healthBotmaker,
                'sidebarHealthBitrix' => $healthBitrix,
            ]);
        });
    }
}
