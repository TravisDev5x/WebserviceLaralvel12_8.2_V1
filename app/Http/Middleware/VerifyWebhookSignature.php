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
            $incoming = (string) $request->header('auth-bm-token', '');

            if ($incoming === '') {
                Log::channel('webhook')->warning('Webhook sin token', [
                    'source' => 'botmaker',
                    'ip' => $request->ip(),
                ]);

                return new JsonResponse(['error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
            }

            $tokens = AuthorizedToken::query()
                ->where('platform', 'botmaker')
                ->where('is_active', true)
                ->pluck('token')
                ->all();

            $matched = false;
            foreach ($tokens as $dbToken) {
                if (hash_equals((string) $dbToken, $incoming)) {
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                $fallback = (string) config('services.botmaker.webhook_secret', '');
                if ($fallback !== '' && hash_equals($fallback, $incoming)) {
                    $matched = true;
                }
            }

            if (! $matched) {
                Log::channel('webhook')->info('DEBUG VALIDACION', [
                    'incoming_token' => $incoming,
                    'tokens_in_db' => $tokens,
                    'matched' => $matched,
                    'fallback' => config('services.botmaker.webhook_secret', ''),
                ]);

                Log::channel('webhook')->warning('Token no reconocido', [
                    'source' => 'botmaker',
                    'ip' => $request->ip(),
                    'token_received' => substr($incoming, 0, 10).'...',
                    'tokens_in_db' => count($tokens),
                ]);

                return new JsonResponse(['error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
            }

            return $next($request);
        }

        return $next($request);
    }
}
