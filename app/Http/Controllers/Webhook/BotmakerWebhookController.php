<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBotmakerPayload;
use App\Models\AuthorizedToken;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BotmakerWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $summary = $this->extractSummaryFields($payload);
        $eventType = (string) ($payload['type'] ?? 'unknown');

        $log = WebhookLog::logIncoming(
            direction: WebhookLog::DIRECTION_BOTMAKER_TO_BITRIX,
            sourceEvent: (string) ($payload['event'] ?? $payload['type'] ?? 'unknown'),
            payloadIn: $payload,
            externalId: $summary['phone'] !== '' ? $summary['phone'] : (string) ($payload['contact']['id'] ?? $payload['contactId'] ?? $payload['customerId'] ?? $payload['external_id'] ?? ''),
            sourceIp: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
        );

        if (! $this->isSignatureValid($request)) {
            $log->markAsFailed('Firma inválida', WebhookLog::ERROR_AUTH, Response::HTTP_UNAUTHORIZED);

            return response()->json([
                'error' => 'Invalid signature',
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Solo procesar mensajes. Eventos de status/event se registran y no se reenvían.
        if ($eventType === 'status') {
            $log->markAsSent(
                httpStatus: Response::HTTP_OK,
                responseBody: 'Status event logged (not forwarded)',
            );

            return response()->json([
                'status' => 'logged',
                'type' => 'status',
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_OK);
        }

        if ($eventType === 'event') {
            $log->markAsSent(
                httpStatus: Response::HTTP_OK,
                responseBody: 'Platform event logged (not forwarded)',
            );

            return response()->json([
                'status' => 'logged',
                'type' => 'event',
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_OK);
        }

        if ($eventType !== 'message') {
            $log->markAsSent(
                httpStatus: Response::HTTP_OK,
                responseBody: "Unsupported event type '{$eventType}' (not forwarded)",
            );

            return response()->json([
                'status' => 'ignored',
                'type' => $eventType,
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_OK);
        }

        if ($summary['phone'] === '') {
            $log->markAsFailed('Payload inválido: teléfono requerido', WebhookLog::ERROR_VALIDATION, Response::HTTP_UNPROCESSABLE_ENTITY);

            return response()->json([
                'status' => 'error',
                'message' => 'Payload inválido: falta teléfono',
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        ProcessBotmakerPayload::dispatch($log->id)->onQueue('webhooks');

        return response()->json([
            'status' => 'accepted',
            'correlation_id' => $log->correlation_id,
        ], Response::HTTP_OK);
    }

    private function isSignatureValid(Request $request): bool
    {
        $incoming = (string) $request->header('auth-bm-token', '');

        if ($incoming === '') {
            return false;
        }

        $tokens = AuthorizedToken::query()
            ->where('platform', 'botmaker')
            ->where('is_active', true)
            ->pluck('token')
            ->all();

        foreach ($tokens as $dbToken) {
            if (hash_equals((string) $dbToken, $incoming)) {
                return true;
            }
        }

        $fallback = (string) config('services.botmaker.webhook_secret', '');

        return $fallback !== '' && hash_equals($fallback, $incoming);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{name:string,phone:string,email:string,status:string}
     */
    private function extractSummaryFields(array $payload): array
    {
        $name = trim((string) ($payload['firstName'] ?? $payload['contact']['firstName'] ?? $payload['name'] ?? ''));
        $phone = trim((string) ($payload['contactId'] ?? $payload['whatsappNumber'] ?? $payload['contact']['phone'] ?? $payload['phone'] ?? ''));
        $email = trim((string) ($payload['email'] ?? $payload['contact']['email'] ?? ''));
        $status = trim((string) ($payload['status'] ?? $payload['contact']['status'] ?? ''));

        return [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'status' => $status,
        ];
    }
}
