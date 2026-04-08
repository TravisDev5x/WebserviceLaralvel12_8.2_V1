<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureRoleOps;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: static function (): void {
            RateLimiter::for('webhooks', function (Request $request): Limit {
                return Limit::perMinute(120)->by((string) $request->ip());
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verify.webhook.signature' => VerifyWebhookSignature::class,
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
            'role.ops' => EnsureRoleOps::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Keep Laravel's default auth handling (redirect/401) intact.
            if ($e instanceof AuthenticationException) {
                return null;
            }

            if ($request->expectsJson()) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            if ($status >= 500) {
                return response()->view('errors.500-friendly', [], 500);
            }

            return null;
        });
    })->create();
