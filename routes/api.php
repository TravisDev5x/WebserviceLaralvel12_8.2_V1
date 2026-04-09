<?php

declare(strict_types=1);

use App\Http\Controllers\Bitrix24\Bitrix24OAuthController;
use App\Http\Controllers\Webhook\BotmakerWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhook')
    ->middleware(['verify.webhook.signature', 'throttle:webhooks'])
    ->group(function (): void {
        Route::post('/botmaker', [BotmakerWebhookController::class, 'handle']);
    });

Route::get('webhook/botmaker', fn () => response()->json(['status' => 'ok', 'service' => 'botmaker_webhook']));

Route::prefix('bitrix24')->group(function (): void {
    Route::match(['get', 'post'], '/install', [Bitrix24OAuthController::class, 'install']);
    Route::post('/handler', [Bitrix24OAuthController::class, 'handler']);
});
