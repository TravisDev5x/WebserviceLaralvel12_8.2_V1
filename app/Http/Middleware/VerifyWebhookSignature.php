<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AuthorizedToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        if (str_contains($path, 'webhook/botmaker')) {
            return $this->validateBotmaker($request, $next);
        }

        if (str_contains($path, 'webhook/bitrix24')) {
            return $this->validateBitrix24($request, $next);
        }

        return $next($request);
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    private function validateBotmaker(Request $request, Closure $next): Response
    {
        $incoming = (string) $request->header('X-Botmaker-Signature', '');
        $fallback = (string) config_dynamic('botmaker.webhook_secret', config('services.botmaker.webhook_secret', ''));

        if ($this->tokenAccepted('botmaker', $incoming, $fallback)) {
            return $next($request);
        }

        Log::channel('webhook')->warning('Firma de webhook inválida', [
            'source' => 'botmaker',
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'header' => 'X-Botmaker-Signature',
        ]);

        return new JsonResponse([
            'error' => 'Invalid signature',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    private function validateBitrix24(Request $request, Closure $next): Response
    {
        $incoming = (string) $request->input('auth.application_token', '');
        $fallback = (string) config_dynamic('bitrix24.webhook_secret', config('services.bitrix24.webhook_secret', ''));

        if ($this->tokenAccepted('bitrix24', $incoming, $fallback)) {
            return $next($request);
        }

        Log::channel('webhook')->warning('Firma de webhook inválida', [
            'source' => 'bitrix24',
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'token_source' => 'auth.application_token',
        ]);

        return new JsonResponse([
            'error' => 'Invalid signature',
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function tokenAccepted(string $platform, string $incoming, string $fallbackSecret): bool
    {
        if ($incoming === '') {
            return false;
        }

        if (AuthorizedToken::hasActiveForPlatform($platform)) {
            return AuthorizedToken::isValid($platform, $incoming);
        }

        return $fallbackSecret !== '' && hash_equals($fallbackSecret, $incoming);
    }
}
