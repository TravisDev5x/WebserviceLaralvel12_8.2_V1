<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Enums\WebhookDirection;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessBitrix24Payload;
use App\Models\AuthorizedToken;
use App\Models\EventFilter;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Bitrix24WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        $log = WebhookLog::logIncoming(
            direction: WebhookDirection::BitrixToBotmaker->value,
            sourceEvent: (string) ($payload['event'] ?? $payload['event_name'] ?? 'unknown'),
            payloadIn: $payload,
            externalId: (string) ($payload['data']['FIELDS']['ID'] ?? $payload['external_id'] ?? ''),
            sourceIp: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
        );

        if (! $this->isSignatureValid($request)) {
            return response()->json([
                'error' => 'Invalid signature',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $this->isPayloadValid($payload)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payload inválido',
                'data' => [
                    'correlation_id' => $log->correlation_id,
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! EventFilter::shouldProcess('bitrix24', $payload)) {
            $log->status = 'filtered';
            $log->response_body = 'Evento filtrado por regla dinámica';
            $log->save();

            return response()->json([
                'status' => 'filtered',
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_OK);
        }

        ProcessBitrix24Payload::dispatch($log)->onQueue('webhooks');

        return response()->json([
            'status' => 'accepted',
            'correlation_id' => $log->correlation_id,
        ], Response::HTTP_OK);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isPayloadValid(array $payload): bool
    {
        $event = $payload['event'] ?? null;
        $leadId = $payload['data']['FIELDS']['ID'] ?? null;
        $applicationToken = $payload['auth']['application_token'] ?? null;

        return is_string($event)
            && $event !== ''
            && $leadId !== null
            && $applicationToken !== null;
    }

    private function isSignatureValid(Request $request): bool
    {
        $incoming = (string) $request->input('auth.application_token', '');
        if ($incoming === '') {
            return false;
        }

        if (AuthorizedToken::hasActiveForPlatform('bitrix24')) {
            return AuthorizedToken::isValid('bitrix24', $incoming);
        }

        $secret = (string) config_dynamic('bitrix24.webhook_secret', config('services.bitrix24.webhook_secret', ''));

        return $secret !== '' && hash_equals($secret, $incoming);
    }
}
