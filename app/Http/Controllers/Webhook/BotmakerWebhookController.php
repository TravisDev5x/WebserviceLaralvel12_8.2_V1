<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Enums\WebhookDirection;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessBotmakerPayload;
use App\Models\EventFilter;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BotmakerWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        $log = WebhookLog::logIncoming(
            direction: WebhookDirection::BotmakerToBitrix->value,
            sourceEvent: (string) ($payload['event'] ?? $payload['type'] ?? 'unknown'),
            payloadIn: $payload,
            externalId: (string) ($payload['contact']['id'] ?? $payload['contactId'] ?? $payload['customerId'] ?? $payload['external_id'] ?? ''),
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

        if (! EventFilter::shouldProcess('botmaker', $payload)) {
            $log->status = 'filtered';
            $log->response_body = 'Evento filtrado por regla dinámica';
            $log->save();

            return response()->json([
                'status' => 'filtered',
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_OK);
        }

        ProcessBotmakerPayload::dispatch($log)->onQueue('webhooks');

        return response()->json([
            'status' => 'accepted',
            'correlation_id' => $log->correlation_id,
        ], Response::HTTP_OK);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function isPayloadValid(array $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        $event = $payload['event'] ?? $payload['type'] ?? null;
        $phone = $payload['whatsappNumber'] ?? $payload['contact']['phone'] ?? $payload['phone'] ?? null;
        $contact = $payload['contactId'] ?? $payload['customerId'] ?? null;

        return is_string($event)
            && $event !== ''
            && (is_string($phone) || is_string($contact));
    }

    private function isSignatureValid(Request $request): bool
    {
        $incoming = (string) $request->header('X-Botmaker-Signature', '');
        $secret = (string) config('services.botmaker.webhook_secret');

        return $incoming !== '' && $secret !== '' && hash_equals($secret, $incoming);
    }
}
