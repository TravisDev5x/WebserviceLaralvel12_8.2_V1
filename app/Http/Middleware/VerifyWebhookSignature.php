<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        if (str_contains($path, 'webhook/botmaker')) {
            return $this->validate(
                request: $request,
                secret: (string) config('services.botmaker.webhook_secret'),
                header: 'X-Botmaker-Signature',
                next: $next,
                source: 'botmaker',
            );
        }

        if (str_contains($path, 'webhook/bitrix24')) {
            return $this->validateBitrix24(
                request: $request,
                secret: (string) config('services.bitrix24.webhook_secret'),
                next: $next,
                source: 'bitrix24',
            );
        }

        return $next($request);
    }

    /**
     * @param Closure(Request): Response $next
     */
    private function validate(
        Request $request,
        string $secret,
        string $header,
        Closure $next,
        string $source,
    ): Response {
        $incoming = (string) $request->header($header, '');

        if ($incoming !== '' && $secret !== '' && hash_equals($secret, $incoming)) {
            return $next($request);
        }

        Log::channel('webhook')->warning('Firma de webhook inválida', [
            'source' => $source,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'header' => $header,
        ]);

        return new JsonResponse([
            'error' => 'Invalid signature',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param Closure(Request): Response $next
     */
    private function validateBitrix24(
        Request $request,
        string $secret,
        Closure $next,
        string $source,
    ): Response {
        $incoming = (string) $request->input('auth.application_token', '');

        if ($incoming !== '' && $secret !== '' && hash_equals($secret, $incoming)) {
            return $next($request);
        }

        Log::channel('webhook')->warning('Firma de webhook inválida', [
            'source' => $source,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'token_source' => 'auth.application_token',
        ]);

        return new JsonResponse([
            'error' => 'Invalid signature',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
