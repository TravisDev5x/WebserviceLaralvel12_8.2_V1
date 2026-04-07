<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuthorizedToken;
use App\Models\WhatsappNumber;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class BotmakerService
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
        $messages = $data['messages'] ?? [];
        $firstMessage = is_array($messages) ? ($messages[0] ?? []) : [];
        $clientPayload = $this->decodeJson((string) ($firstMessage['clientPayload'] ?? ''));
        $context = is_array($data['context'] ?? null) ? $data['context'] : [];
        $attributes = is_array($data['attributes'] ?? null) ? $data['attributes'] : [];
        $variables = is_array($data['variables'] ?? null) ? $data['variables'] : [];

        $merged = [
            ...$data,
            ...$context,
            ...$attributes,
            ...$variables,
            ...$clientPayload,
        ];

        return [
            'event' => (string) ($data['event'] ?? $data['type'] ?? 'unknown'),
            'phone' => (string) ($data['whatsappNumber'] ?? $data['contact']['phone'] ?? $data['phone'] ?? $data['contactId'] ?? ''),
            'message' => (string) ($firstMessage['message'] ?? $data['message']['text'] ?? $data['text'] ?? ''),
            'first_name' => (string) ($data['firstName'] ?? $this->pickByKey($merged, 'first_name')),
            'last_name' => (string) ($data['lastName'] ?? $this->pickByKey($merged, 'last_name')),
            'middle_last_name' => (string) $this->pickByKey($merged, 'middle_last_name'),
            'birth_date' => (string) $this->pickByKey($merged, 'birth_date'),
            'weeks_quoted' => (string) $this->pickByKey($merged, 'weeks_quoted'),
            'employment_status' => (string) $this->pickByKey($merged, 'employment_status'),
            'last_salary' => (string) $this->pickByKey($merged, 'last_salary'),
            'state' => (string) $this->pickByKey($merged, 'state'),
            'email' => $this->resolveEmailFromMerged($merged),
            'contact_id' => (string) ($data['contactId'] ?? ''),
            'customer_id' => (string) ($data['customerId'] ?? ''),
            'session_id' => (string) ($data['sessionId'] ?? ''),
            'raw' => $data,
        ];
    }

    /**
     * @return array{success: bool, http_status: int, body: string}
     */
    public function sendMessage(string $phone, string $text): array
    {
        $customSend = trim((string) config_dynamic('botmaker.send_message_url', ''));
        $url = $customSend !== ''
            ? rtrim($customSend, '/')
            : rtrim($this->botmakerConfig('api_url', ''), '/').'/chats-actions/send-messages';
        $defaultNumber = WhatsappNumber::getDefault();
        $payload = [
            'chatPlatform' => 'whatsapp',
            'whatsappNumber' => $this->normalizePhone($phone),
            'messages' => [
                [
                    'message' => $text,
                ],
            ],
        ];
        if ($defaultNumber?->platform_id) {
            $payload['chatChannelId'] = (string) $defaultNumber->platform_id;
        }

        return $this->sendRequest(url: $url, payload: $payload, operation: 'send_message');
    }

    /**
     * @param  array<int|string, mixed>  $params
     * @return array{success: bool, http_status: int, body: string}
     */
    public function sendTemplate(string $phone, string $template, array $params): array
    {
        $url = rtrim($this->botmakerConfig('api_url', ''), '/').'/templates/send';
        $payload = [
            'phone' => $phone,
            'template' => $template,
            'params' => $params,
        ];

        return $this->sendRequest(url: $url, payload: $payload, operation: 'send_template');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, http_status: int, body: string}
     */
    private function sendRequest(string $url, array $payload, string $operation): array
    {
        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'access-token' => (string) $this->botmakerConfig('api_token', ''),
                    // Backward compatibility with previous configuration.
                    'Authorization' => 'Bearer '.$this->botmakerConfig('api_token', ''),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => (int) config_dynamic('retry.http_timeout', 15),
            ]);

            $httpStatus = $response->getStatusCode();
            $body = (string) $response->getBody();
            $success = $httpStatus >= 200 && $httpStatus < 300;

            Log::channel('webhook')->debug('Botmaker request exitosa', [
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
            Log::channel('webhook')->error('Botmaker request fallida', [
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

    private function botmakerConfig(string $key, mixed $default = null): mixed
    {
        if ($this->config !== []) {
            return $this->config[$key] ?? $default;
        }

        if ($key === 'api_token') {
            $fromDb = AuthorizedToken::getPrimaryBotmakerApiToken();
            if (is_string($fromDb) && $fromDb !== '') {
                return $fromDb;
            }
        }

        return config_dynamic("botmaker.{$key}", config("services.botmaker.{$key}", $default));
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }

    /**
     * @param  array<string, mixed>  $merged
     */
    private function resolveEmailFromMerged(array $merged): string
    {
        $fromAliases = $this->pickByKey($merged, 'email');
        if (is_string($fromAliases) && trim($fromAliases) !== '') {
            return trim($fromAliases);
        }

        foreach (['email', 'correo', 'mail', 'e_mail', 'emailAddress'] as $key) {
            if (isset($merged[$key]) && is_string($merged[$key]) && trim($merged[$key]) !== '') {
                return trim($merged[$key]);
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function pickByKey(array $source, string $key): mixed
    {
        $sourceAliases = config_dynamic('botmaker.source_aliases', config('integrations.botmaker_to_bitrix.source_aliases', []));
        $aliases = is_array($sourceAliases) ? ($sourceAliases[$key] ?? []) : [];
        if (! is_array($aliases) || $aliases === []) {
            return '';
        }

        /** @var array<int, string> $aliases */
        return $this->pick($source, $aliases);
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<int, string>  $keys
     */
    private function pick(array $source, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $source) && $source[$key] !== null && $source[$key] !== '') {
                return $source[$key];
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
