<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuthorizedToken;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class Bitrix24Service
{
    private readonly ClientInterface $httpClient;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        ?ClientInterface $httpClient = null,
        private readonly array $config = [],
    ) {
        $timeout = (int) config_dynamic('retry.http_timeout', 15);
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => $timeout,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function parseIncomingPayload(array $data): array
    {
        // TODO: Ajustar mapeo cuando Bitrix24 comparta payload real.
        return [
            'event' => (string) ($data['event'] ?? $data['event_name'] ?? 'unknown'),
            'lead_id' => isset($data['data']['FIELDS']['ID']) ? (int) $data['data']['FIELDS']['ID'] : null,
            'phone' => (string) ($data['data']['FIELDS']['PHONE'][0]['VALUE'] ?? ''),
            'message' => (string) ($data['data']['FIELDS']['COMMENTS'] ?? ''),
            'name' => (string) ($data['data']['FIELDS']['NAME'] ?? ''),
            'last_name' => (string) ($data['data']['FIELDS']['LAST_NAME'] ?? ''),
            'status' => (string) ($data['data']['FIELDS']['STATUS_ID'] ?? ''),
            'agente' => (string) ($data['auth']['user_id'] ?? ''),
            'should_notify' => true,
            'raw' => $data,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, http_status: int, body: string}
     */
    public function createLead(array $data): array
    {
        return $this->post(method: 'crm.lead.add', payload: ['fields' => $data], operation: 'create_lead');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, http_status: int, body: string}
     */
    public function updateLead(int $id, array $data): array
    {
        return $this->post(
            method: 'crm.lead.update',
            payload: ['id' => $id, 'fields' => $data],
            operation: 'update_lead',
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findContactByPhone(string $phone): ?array
    {
        $response = $this->post(
            method: 'crm.contact.list',
            payload: ['filter' => ['PHONE' => $phone], 'select' => ['ID', 'NAME']],
            operation: 'find_contact_by_phone',
        );

        if (! $response['success']) {
            return null;
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($response['body'], true);
        if (! is_array($decoded)) {
            return null;
        }

        $result = $decoded['result'] ?? null;

        return is_array($result) ? ($result[0] ?? null) : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, http_status: int, body: string}
     */
    private function post(string $method, array $payload, string $operation): array
    {
        $baseUrl = rtrim((string) $this->bitrixConfig('webhook_url', ''), '/');
        $url = "{$baseUrl}/{$method}";

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => (int) config_dynamic('retry.http_timeout', 15),
            ]);

            $httpStatus = $response->getStatusCode();
            $body = (string) $response->getBody();
            $success = $httpStatus >= 200 && $httpStatus < 300;

            Log::channel('webhook')->debug('Bitrix24 request ejecutada', [
                'operation' => $operation,
                'url' => $url,
                'http_status' => $httpStatus,
                'response_body' => $body,
            ]);

            return [
                'success' => $success,
                'http_status' => $httpStatus,
                'body' => $body,
            ];
        } catch (Throwable $exception) {
            Log::channel('webhook')->error('Bitrix24 request fallida', [
                'operation' => $operation,
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'http_status' => 0,
                'body' => $exception->getMessage(),
            ];
        }
    }

    private function bitrixConfig(string $key, mixed $default = null): mixed
    {
        if ($this->config !== []) {
            return $this->config[$key] ?? $default;
        }

        if ($key === 'webhook_url') {
            return AuthorizedToken::resolvedBitrix24WebhookUrl();
        }

        return config_dynamic("bitrix24.{$key}", config("services.bitrix24.{$key}", $default));
    }
}
