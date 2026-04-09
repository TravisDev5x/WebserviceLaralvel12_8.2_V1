<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bitrix24;

use App\Http\Controllers\Controller;
use App\Jobs\SendBotmakerMessage;
use App\Models\Bitrix24Token;
use App\Models\WebhookLog;
use App\Services\Bitrix24AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Bitrix24OAuthController extends Controller
{
    public function __construct(
        private readonly Bitrix24AuthService $authService,
    ) {}

    /**
     * Receives initial OAuth tokens when the App Local is installed in Bitrix24.
     * Endpoint: GET|POST /api/bitrix24/install
     */
    public function install(Request $request): JsonResponse
    {
        $payload = $request->all();

        $log = WebhookLog::logIncoming(
            direction: WebhookLog::DIRECTION_BITRIX_TO_BOTMAKER,
            sourceEvent: 'bitrix24_app_install',
            payloadIn: $payload,
            sourceIp: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
        );

        try {
            $authData = $payload['auth'] ?? $payload;

            $accessToken = (string) ($authData['access_token'] ?? '');
            $refreshToken = (string) ($authData['refresh_token'] ?? '');

            if ($accessToken === '' || $refreshToken === '') {
                $log->markAsFailed(
                    'Install payload missing access_token or refresh_token',
                    WebhookLog::ERROR_VALIDATION,
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );

                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing OAuth tokens in install payload',
                    'correlation_id' => $log->correlation_id,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $token = $this->authService->storeTokensFromInstall($authData);

            $log->markAsSent(
                httpStatus: Response::HTTP_OK,
                responseBody: "Tokens stored for domain: {$token->domain}",
            );

            Log::channel('webhook')->info('Bitrix24 App Local installed successfully', [
                'domain' => $token->domain,
                'correlation_id' => $log->correlation_id,
            ]);

            return response()->json([
                'status' => 'installed',
                'domain' => $token->domain,
                'correlation_id' => $log->correlation_id,
            ]);
        } catch (Throwable $e) {
            $log->markAsFailed($e->getMessage(), WebhookLog::ERROR_UNKNOWN);

            Log::channel('webhook')->error('Bitrix24 App install failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $log->correlation_id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Installation failed',
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Main entry point for all Bitrix24 App Local events.
     * Endpoint: POST /api/bitrix24/handler
     */
    public function handler(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event = (string) ($payload['event'] ?? 'unknown');

        $log = WebhookLog::logIncoming(
            direction: WebhookLog::DIRECTION_BITRIX_TO_BOTMAKER,
            sourceEvent: $event,
            payloadIn: $payload,
            sourceIp: (string) $request->ip(),
            userAgent: (string) $request->userAgent(),
        );

        if (! $this->isApplicationTokenValid($payload)) {
            $log->markAsFailed(
                'Invalid application_token',
                WebhookLog::ERROR_AUTH,
                Response::HTTP_UNAUTHORIZED,
            );

            Log::channel('webhook')->warning('Bitrix24 handler: invalid application_token', [
                'event' => $event,
                'correlation_id' => $log->correlation_id,
            ]);

            return response()->json([
                'error' => 'Invalid application_token',
                'correlation_id' => $log->correlation_id,
            ], Response::HTTP_UNAUTHORIZED);
        }

        Log::channel('webhook')->info('Bitrix24 handler: event received', [
            'event' => $event,
            'correlation_id' => $log->correlation_id,
        ]);

        return $this->handleEvent($event, $payload, $log);
    }

    // =========================================================================
    //  Event Router
    // =========================================================================

    private function handleEvent(string $event, array $payload, WebhookLog $log): JsonResponse
    {
        $normalizedEvent = mb_strtoupper($event);

        return match ($normalizedEvent) {
            'ONIMCONNECTORMESSAGEADD' => $this->handleImConnectorMessageAdd($payload, $log),
            default => $this->handleUnknownEvent($event, $log),
        };
    }

    // =========================================================================
    //  OnImConnectorMessageAdd — Agent replies from Bitrix24 Open Channel
    // =========================================================================

    private function handleImConnectorMessageAdd(array $payload, WebhookLog $log): JsonResponse
    {
        $data = $payload['data'] ?? $payload;

        $connector = (string) ($data['CONNECTOR'] ?? $data['connector'] ?? '');
        $configuredConnector = (string) config_dynamic('bitrix24.connector_id', config('services.bitrix24.connector_id', 'botmaker_whatsapp'));

        // T4.4: Ignore events that are NOT from our connector
        if ($connector !== '' && $connector !== $configuredConnector) {
            $log->markAsSent(
                httpStatus: Response::HTTP_OK,
                responseBody: "Ignored: connector mismatch ({$connector} != {$configuredConnector})",
            );

            return response()->json([
                'status' => 'ignored',
                'reason' => 'connector_mismatch',
                'correlation_id' => $log->correlation_id,
            ]);
        }

        $messages = $data['MESSAGES'] ?? $data['messages'] ?? [];

        if (! is_array($messages) || $messages === []) {
            $log->markAsSent(
                httpStatus: Response::HTTP_OK,
                responseBody: 'No messages in event payload',
            );

            return response()->json([
                'status' => 'ignored',
                'reason' => 'no_messages',
                'correlation_id' => $log->correlation_id,
            ]);
        }

        $dispatched = 0;

        foreach ($messages as $msg) {
            if (! is_array($msg)) {
                continue;
            }

            // T4.4: Ignore system messages — only process human agent messages
            $systemMessage = $msg['system'] ?? $msg['SYSTEM'] ?? false;
            if ($systemMessage === true || $systemMessage === 'Y' || $systemMessage === '1') {
                Log::channel('webhook')->debug('Skipping system message', [
                    'correlation_id' => $log->correlation_id,
                ]);

                continue;
            }

            // T4.4: Ignore messages injected by this WebService (anti-loop)
            $externalSource = (string) ($msg['extra']['source'] ?? '');
            if ($externalSource === 'webservice_connector') {
                Log::channel('webhook')->debug('Skipping self-injected message (anti-loop)', [
                    'correlation_id' => $log->correlation_id,
                ]);

                continue;
            }

            // T5.2: Idempotency — skip already-processed Bitrix24 message IDs
            $bitrixMsgId = (string) ($msg['id'] ?? $msg['ID'] ?? $msg['message']['id'] ?? '');
            if ($bitrixMsgId !== '') {
                $idempotencyKey = "b24_msg_{$bitrixMsgId}";
                if (Cache::has($idempotencyKey)) {
                    Log::channel('webhook')->debug('Skipping duplicate Bitrix24 message', [
                        'message_id' => $bitrixMsgId,
                        'correlation_id' => $log->correlation_id,
                    ]);

                    continue;
                }

                Cache::put($idempotencyKey, true, 300);
            }

            $rawMessage = $msg['message'] ?? null;
            $messageText = (string) ($msg['message']['text'] ?? $msg['MESSAGE'] ?? (is_string($rawMessage) ? $rawMessage : ''));
            $chatId = (string) ($msg['chat']['id'] ?? $msg['CHAT_ID'] ?? $msg['chat_id'] ?? '');
            $userId = (string) ($msg['user']['id'] ?? $msg['USER_ID'] ?? $msg['user_id'] ?? '');

            // The chat.id in our connector maps to the client's phone number
            $phone = $chatId !== '' ? $chatId : $userId;

            if ($phone === '' || $messageText === '') {
                Log::channel('webhook')->debug('Skipping message: missing phone or text', [
                    'phone' => $phone,
                    'text_empty' => $messageText === '',
                    'correlation_id' => $log->correlation_id,
                ]);

                continue;
            }

            $agentName = (string) ($msg['user']['name'] ?? $msg['USER_NAME'] ?? 'agent');

            Log::channel('webhook')->info('Dispatching SendBotmakerMessage', [
                'phone' => $phone,
                'agent' => $agentName,
                'correlation_id' => $log->correlation_id,
            ]);

            SendBotmakerMessage::dispatch(
                $phone,
                $messageText,
                $log->correlation_id,
                $log->id,
            )->onQueue('webhooks');

            $dispatched++;
        }

        if ($dispatched === 0) {
            $log->markAsSent(
                httpStatus: Response::HTTP_OK,
                responseBody: 'All messages filtered out (system/loop/empty)',
            );

            return response()->json([
                'status' => 'filtered',
                'reason' => 'no_actionable_messages',
                'correlation_id' => $log->correlation_id,
            ]);
        }

        $log->markAsSent(
            httpStatus: Response::HTTP_OK,
            responseBody: "Dispatched {$dispatched} message(s) to Botmaker queue",
        );

        return response()->json([
            'status' => 'accepted',
            'dispatched' => $dispatched,
            'correlation_id' => $log->correlation_id,
        ]);
    }

    // =========================================================================
    //  Unknown / unhandled events
    // =========================================================================

    private function handleUnknownEvent(string $event, WebhookLog $log): JsonResponse
    {
        $log->markAsSent(
            httpStatus: Response::HTTP_OK,
            responseBody: "Event {$event} acknowledged (no handler)",
        );

        return response()->json([
            'status' => 'acknowledged',
            'event' => $event,
            'correlation_id' => $log->correlation_id,
        ]);
    }

    // =========================================================================
    //  Validation
    // =========================================================================

    private function isApplicationTokenValid(array $payload): bool
    {
        $incoming = (string) ($payload['auth']['application_token'] ?? $payload['application_token'] ?? '');

        if ($incoming === '') {
            return false;
        }

        $stored = Bitrix24Token::getActive();

        if ($stored instanceof Bitrix24Token && $stored->application_token) {
            return hash_equals((string) $stored->application_token, $incoming);
        }

        return false;
    }
}
