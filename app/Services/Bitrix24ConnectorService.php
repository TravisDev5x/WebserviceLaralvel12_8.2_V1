<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class Bitrix24ConnectorService
{
    private readonly ClientInterface $httpClient;

    private readonly Bitrix24AuthService $authService;

    public function __construct(
        Bitrix24AuthService $authService,
        ?ClientInterface $httpClient = null,
    ) {
        $this->authService = $authService;
        $this->httpClient = $httpClient ?? new Client(['timeout' => 20]);
    }

    /**
     * imconnector.register — Registers the custom connector type in Bitrix24.
     *
     * @return array<string, mixed>
     */
    public function registerConnector(): array
    {
        $connectorId = $this->connectorId();

        $appUrl = rtrim((string) config('app.url', ''), '/');

        return $this->callRest('imconnector.register', [
            'ID' => $connectorId,
            'NAME' => 'Botmaker WhatsApp',
            'ICON' => [
                'DATA_IMAGE' => $this->whatsappIconBase64(),
                'COLOR' => '#25D366',
                'SIZE' => '60',
            ],
            'PLACEMENT_HANDLER' => $appUrl . '/api/bitrix24/handler',
        ], 'register_connector');
    }

    /**
     * imconnector.activate — Activates the connector for a specific Open Channel line.
     *
     * @return array<string, mixed>
     */
    public function activateConnector(int $lineId): array
    {
        return $this->callRest('imconnector.activate', [
            'CONNECTOR' => $this->connectorId(),
            'LINE' => $lineId,
            'ACTIVE' => 1,
        ], 'activate_connector');
    }

    /**
     * imconnector.connector.data.set — Sets display info for the connector on a line.
     *
     * @return array<string, mixed>
     */
    public function setConnectorData(int $lineId): array
    {
        $appUrl = rtrim((string) config('app.url', ''), '/');

        return $this->callRest('imconnector.connector.data.set', [
            'CONNECTOR' => $this->connectorId(),
            'LINE' => $lineId,
            'DATA' => [
                'id' => $this->connectorId(),
                'name' => 'Botmaker WhatsApp',
                'icon' => [
                    'data_image' => $this->whatsappIconBase64(),
                    'color' => '#25D366',
                    'size' => '60',
                ],
                'icon_disabled' => '',
                'url_im' => $appUrl,
            ],
        ], 'set_connector_data');
    }

    /**
     * imconnector.status — Checks if the connector is active and ready on a line.
     *
     * @return array<string, mixed>
     */
    public function checkStatus(int $lineId): array
    {
        return $this->callRest('imconnector.status', [
            'CONNECTOR' => $this->connectorId(),
            'LINE' => $lineId,
        ], 'check_connector_status');
    }

    /**
     * imconnector.send.messages — Injects an incoming customer message into the Open Channel.
     *
     * @param  array<int, array{user: array{id: string, name: string}, message: array{text: string}, chat: array{id: string}}> $messages
     * @return array<string, mixed>
     */
    public function sendMessageToChannel(string $lineId, array $messages): array
    {
        return $this->callRest('imconnector.send.messages', [
            'CONNECTOR' => $this->connectorId(),
            'LINE' => $lineId,
            'MESSAGES' => $messages,
        ], 'send_message_to_channel');
    }

    /**
     * Convenience wrapper: builds the MESSAGES array from simple params and calls sendMessageToChannel.
     *
     * @return array<string, mixed>
     */
    public function sendSingleMessage(string $phoneNumber, string $clientName, string $messageText): array
    {
        $lineId = (string) config_dynamic('bitrix24.line_id', config('services.bitrix24.line_id', '1'));

        $messages = [
            [
                'user' => [
                    'id' => $phoneNumber,
                    'name' => $clientName,
                ],
                'message' => [
                    'text' => $messageText,
                ],
                'chat' => [
                    'id' => $phoneNumber,
                ],
            ],
        ];

        return $this->sendMessageToChannel($lineId, $messages);
    }

    /**
     * imconnector.send.status.delivery — Confirms message delivery to Bitrix24.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sendDeliveryStatus(string $lineId, array $data): array
    {
        return $this->callRest('imconnector.send.status.delivery', array_merge([
            'CONNECTOR' => $this->connectorId(),
            'LINE' => $lineId,
        ], $data), 'send_delivery_status');
    }

    /**
     * Executes a REST API call to Bitrix24 using OAuth token.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function callRest(string $method, array $params, string $operation): array
    {
        $accessToken = $this->authService->getValidToken();
        $domain = (string) config_dynamic('bitrix24.domain', config('services.bitrix24.domain', ''));

        if ($domain === '') {
            throw new RuntimeException('BITRIX24_DOMAIN is not configured.');
        }

        $url = "https://{$domain}/rest/{$method}";

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => array_merge($params, [
                    'auth' => $accessToken,
                ]),
            ]);

            $httpStatus = $response->getStatusCode();
            $body = (string) $response->getBody();

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($body, true) ?? [];

            Log::channel('webhook')->debug("Bitrix24 REST: {$operation}", [
                'method' => $method,
                'http_status' => $httpStatus,
                'response' => $decoded,
            ]);

            if (isset($decoded['error'])) {
                throw new RuntimeException(
                    "Bitrix24 REST error in {$method}: {$decoded['error']} — "
                    . ($decoded['error_description'] ?? 'no description')
                );
            }

            return [
                'success' => true,
                'http_status' => $httpStatus,
                'body' => $body,
                'data' => $decoded,
            ];
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::channel('webhook')->error("Bitrix24 REST failed: {$operation}", [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                "Bitrix24 REST call {$method} failed: {$e->getMessage()}", 0, $e
            );
        }
    }

    private function connectorId(): string
    {
        return (string) config_dynamic('bitrix24.connector_id', config('services.bitrix24.connector_id', 'botmaker_whatsapp'));
    }

    private function whatsappIconBase64(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">'
            . '<circle cx="30" cy="30" r="30" fill="#25D366"/>'
            . '<path d="M40.4 35.5c-.7-.4-4-2-4.7-2.2-.6-.2-1-.3-1.5.3-.4.6-1.7 2.2-2.1 2.6-.4.5-.8.5-1.4.2-.7-.4-3-1.1-5.6-3.5-2.1-1.9-3.5-4.2-3.9-4.9-.4-.7 0-.9.3-1.3.3-.3.7-.8 1-1.2.3-.4.4-.7.6-1.1.2-.4.1-.8 0-1.1-.2-.4-1.5-3.6-2-4.9-.5-1.3-1.1-1.1-1.5-1.1h-1.3c-.4 0-1.1.2-1.7.8-.6.7-2.3 2.2-2.3 5.4s2.3 6.3 2.7 6.7c.3.4 4.6 7 11.1 9.8 1.6.7 2.8 1.1 3.7 1.4 1.6.5 3 .4 4.1.3 1.3-.2 4-1.6 4.5-3.2.6-1.6.6-2.9.4-3.2-.2-.3-.6-.5-1.3-.8z" fill="#fff"/>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
