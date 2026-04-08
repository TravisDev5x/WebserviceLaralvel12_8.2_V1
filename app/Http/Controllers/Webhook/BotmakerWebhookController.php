<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\AuthorizedToken;
use App\Models\FailedWebhook;
use App\Models\FieldMapping;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class BotmakerWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        $log = WebhookLog::logIncoming(
            direction: WebhookLog::DIRECTION_BOTMAKER_TO_BITRIX,
            sourceEvent: (string) ($payload['event'] ?? $payload['type'] ?? 'unknown'),
            payloadIn: $payload,
            externalId: (string) ($payload['contact']['id'] ?? $payload['contactId'] ?? $payload['customerId'] ?? $payload['external_id'] ?? ''),
            sourceIp: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
        );

        if (! $this->isSignatureValid($request)) {
            $log->markAsFailed('Firma inválida', WebhookLog::ERROR_AUTH, Response::HTTP_UNAUTHORIZED);

            return response()->json([
                'error' => 'Invalid signature',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $leadData = $this->buildLeadData($payload);
        if ($leadData['PHONE'] === '') {
            $log->markAsFailed('Payload inválido: teléfono requerido', WebhookLog::ERROR_VALIDATION, Response::HTTP_UNPROCESSABLE_ENTITY);

            return response()->json([
                'status' => 'error',
                'message' => 'Payload inválido: falta teléfono',
                'data' => [
                    'correlation_id' => $log->correlation_id,
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $log->markAsProcessing();
        $bitrixBaseUrl = rtrim(AuthorizedToken::resolvedBitrix24WebhookUrl(), '/');
        if ($bitrixBaseUrl === '') {
            $error = 'No hay webhook URL de Bitrix24 configurada';
            $log->markAsFailed($error, WebhookLog::ERROR_VALIDATION, 0);
            FailedWebhook::createFromLog($log, $payload, $bitrixBaseUrl, $error, 0);

            return response()->json([
                'status' => 'error',
                'message' => $error,
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $targetUrl = $bitrixBaseUrl.'/crm.lead.add';

        try {
            $response = Http::acceptJson()
                ->timeout((int) config_dynamic('retry.http_timeout', 15))
                ->post($targetUrl, ['fields' => $leadData]);

            $log->payload_out = ['fields' => $leadData];
            $log->http_status = $response->status();
            $log->response_body = $response->body();

            if ($response->successful()) {
                $log->status = WebhookLog::STATUS_SENT;
                $log->save();

                return response()->json([
                    'status' => 'accepted',
                    'correlation_id' => $log->correlation_id,
                ], Response::HTTP_OK);
            }

            $error = 'Error Bitrix24: HTTP '.$response->status();
            $log->markAsFailed($error, WebhookLog::ERROR_SERVER, $response->status());
            FailedWebhook::createFromLog($log, $payload, $targetUrl, $response->body(), $response->status());

            return response()->json([
                'status' => 'error',
                'message' => $error,
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_BAD_GATEWAY);
        } catch (\Throwable $exception) {
            $log->markAsFailed($exception->getMessage(), WebhookLog::ERROR_UNKNOWN, 0);
            FailedWebhook::createFromLog($log, $payload, $targetUrl, $exception->getMessage(), 0);

            return response()->json([
                'status' => 'error',
                'message' => 'Error de red al enviar a Bitrix24',
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    private function isSignatureValid(Request $request): bool
    {
        $incoming = (string) $request->header('X-Botmaker-Signature', '');
        if ($incoming === '') {
            return false;
        }

        if (AuthorizedToken::hasActiveForPlatform('botmaker')) {
            return AuthorizedToken::isValid('botmaker', $incoming);
        }

        $secret = (string) config_dynamic('botmaker.webhook_secret', config('services.botmaker.webhook_secret', ''));

        return $secret !== '' && hash_equals($secret, $incoming);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildLeadData(array $payload): array
    {
        $name = (string) ($payload['firstName'] ?? $payload['contact']['firstName'] ?? $payload['name'] ?? '');
        $lastName = (string) ($payload['lastName'] ?? $payload['contact']['lastName'] ?? '');
        $phone = (string) ($payload['whatsappNumber'] ?? $payload['contact']['phone'] ?? $payload['phone'] ?? '');
        $email = (string) ($payload['email'] ?? $payload['contact']['email'] ?? '');
        $status = (string) ($payload['status'] ?? $payload['contact']['status'] ?? '');

        $lead = [
            'NAME' => $name,
            'LAST_NAME' => $lastName,
            'PHONE' => $phone,
            'EMAIL' => $email,
            'STATUS_ID' => $status,
            'TITLE' => trim($name.' '.$lastName) !== '' ? trim($name.' '.$lastName) : 'Lead Botmaker',
        ];

        $mappings = FieldMapping::getMappings('botmaker');
        foreach ($mappings as $mapping) {
            $targetField = (string) ($mapping->target_field ?? '');
            if ($targetField === '') {
                continue;
            }
            $value = data_get($payload, (string) ($mapping->source_path ?? ''));
            $transformed = $mapping->applyTransform($value);
            if ($transformed === null || $transformed === '') {
                continue;
            }
            $lead[$targetField] = $transformed;
        }

        return $lead;
    }
}
