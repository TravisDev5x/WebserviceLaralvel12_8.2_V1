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

        return $this->callRest('imconnector.register', [
            'ID' => $connectorId,
            'NAME' => 'Botmaker WhatsApp',
            'ICON' => [
                'DATA_IMAGE' => '',
                'COLOR' => '#25D366',
                'SIZE' => '60',
            ],
            'PLACEMENT_HANDLER' => '',
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
                    'data_image' => '',
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
        $lineId = (string) config('services.bitrix24.line_id', '1');

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
        $domain = (string) config('services.bitrix24.domain', '');

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
        return (string) config('services.bitrix24.connector_id', 'botmaker_whatsapp');
    }
}
