<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BotmakerApiException;
use App\Models\AuthorizedToken;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

final class BotmakerService
{
    private readonly ClientInterface $httpClient;

    public function __construct(?ClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 15,
        ]);
    }

    /**
     * Normaliza payload entrante de Botmaker a un formato estable para el job.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function parseIncomingPayload(array $payload): array
    {
        $firstName = (string) data_get($payload, 'firstName', data_get($payload, 'contact.firstName', ''));
        $lastName = (string) data_get($payload, 'lastName', data_get($payload, 'contact.lastName', ''));
        $phone = (string) data_get($payload, 'whatsappNumber', data_get($payload, 'contact.phone', data_get($payload, 'phone', '')));
        $email = (string) data_get($payload, 'email', data_get($payload, 'contact.email', ''));
        $status = (string) data_get($payload, 'status', data_get($payload, 'contact.status', ''));
        $message = (string) data_get($payload, 'message.text', data_get($payload, 'message', ''));
        $event = (string) data_get($payload, 'event', data_get($payload, 'type', 'unknown'));

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'status' => $status,
            'message' => $message,
            'event' => $event,
            'raw' => $payload,
        ];
    }

    /**
     * Sends a text message to a WhatsApp number via Botmaker's API.
     *
     * @return array{success: bool, http_status: int, body: string, data: array<string, mixed>}
     *
     * @throws BotmakerApiException
     */
    public function sendMessage(string $phone, string $text): array
    {
        $baseUrl = rtrim(AuthorizedToken::resolvedBotmakerApiUrl() ?: 'https://api.botmaker.com/v2.0', '/');
        $sendEndpoint = (string) config_dynamic('botmaker.send_endpoint', config('services.botmaker.send_endpoint', '/chats-actions/send-messages'));
        $url = $baseUrl . $sendEndpoint;

        $apiToken = AuthorizedToken::resolvedBotmakerApiToken();

        if ($apiToken === '') {
            throw new BotmakerApiException(
                'Botmaker API Token no configurado. Configúralo desde el panel en Conexión Botmaker > Token de API.',
                context: ['phone' => $phone],
            );
        }

        $channelId = trim((string) config_dynamic('botmaker.channel_id', ''));
        if ($channelId === '') {
            throw new BotmakerApiException(
                'Channel ID de Botmaker no configurado. Configúralo en el panel > Conexión Botmaker > Channel ID.',
                context: ['phone' => $phone],
            );
        }

        $payload = [
            'chat' => [
                'channelId' => $channelId,
                'contactId' => $phone,
            ],
            'messages' => [
                ['text' => self::stripBbCode($text)],
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'access-token' => $apiToken,
                ],
                'json' => $payload,
            ]);

            $httpStatus = $response->getStatusCode();
            $body = (string) $response->getBody();

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($body, true) ?? [];
            $success = $httpStatus >= 200 && $httpStatus < 300;

            Log::channel('webhook')->debug('Botmaker sendMessage response', [
                'url' => $url,
                'phone' => $phone,
                'http_status' => $httpStatus,
                'response_body' => $body,
            ]);

            if (! $success) {
                throw new BotmakerApiException(
                    "Botmaker API returned HTTP {$httpStatus}",
                    httpStatus: $httpStatus,
                    responseBody: $body,
                    context: ['phone' => $phone, 'payload' => $payload],
                );
            }

            return [
                'success' => true,
                'http_status' => $httpStatus,
                'body' => $body,
                'data' => $decoded,
            ];
        } catch (BotmakerApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::channel('webhook')->error('Botmaker sendMessage failed', [
                'url' => $url,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw new BotmakerApiException(
                "Botmaker API request failed: {$e->getMessage()}",
                responseBody: $e->getMessage(),
                context: ['phone' => $phone, 'payload' => $payload],
                previous: $e,
            );
        }
    }

    private static function stripBbCode(string $text): string
    {
        $text = preg_replace('/\[br\]/i', "\n", $text);
        $text = preg_replace('/\[\/?[a-zA-Z][a-zA-Z0-9]*(?:=[^\]]*?)?\]/u', '', $text);
        $text = trim($text);

        if ($text !== '') {
            $firstNewline = strpos($text, "\n");
            if ($firstNewline !== false) {
                $firstLine = trim(substr($text, 0, $firstNewline));
                if ($firstLine !== '' && str_ends_with($firstLine, ':')) {
                    $text = trim(substr($text, $firstNewline + 1));
                }
            }

            $text = preg_replace('/^[A-ZÁ-Ú\s]+:\s*\n/u', '', $text);
            $text = trim($text);
        }

        return $text;
    }
}
