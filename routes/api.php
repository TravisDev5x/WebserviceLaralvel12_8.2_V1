<?php

declare(strict_types=1);

use App\Http\Controllers\Webhook\BotmakerWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('webhook')
    ->middleware(['verify.webhook.signature', 'throttle:webhooks'])
    ->group(function (): void {
        Route::post('/botmaker', [BotmakerWebhookController::class, 'handle']);
    });
