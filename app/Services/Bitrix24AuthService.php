<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bitrix24Token;
use App\Models\WebhookLog;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class Bitrix24AuthService
{
    private const OAUTH_TOKEN_URL = 'https://oauth.bitrix.info/oauth/token/';

    private const TOKEN_EXPIRY_BUFFER_SECONDS = 300;

    private readonly ClientInterface $httpClient;

    public function __construct(?ClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Client(['timeout' => 15]);
    }

    /**
     * Returns a valid access_token, refreshing automatically if expired.
     *
     * @throws RuntimeException if no token exists or refresh fails
     */
    public function getValidToken(): string
    {
        $token = Bitrix24Token::getActive();

        if (! $token instanceof Bitrix24Token) {
            throw new RuntimeException(
                'No Bitrix24 OAuth token found. Install the App Local first via /api/bitrix24/install.'
            );
        }

        if ($this->isTokenExpired($token)) {
            $this->refreshToken($token);
            $token->refresh();
        }

        return $token->access_token;
    }

    public function isTokenExpired(?Bitrix24Token $token = null): bool
    {
        $token ??= Bitrix24Token::getActive();

        if (! $token instanceof Bitrix24Token) {
            return true;
        }

        if ($token->expires_at === null) {
            return true;
        }

        return $token->expires_at->subSeconds(self::TOKEN_EXPIRY_BUFFER_SECONDS)->isPast();
    }

    /**
     * @throws RuntimeException if the refresh request fails
     */
    public function refreshToken(?Bitrix24Token $token = null): void
    {
        $token ??= Bitrix24Token::getActive();

        if (! $token instanceof Bitrix24Token) {
            throw new RuntimeException('No Bitrix24 token record to refresh.');
        }

        $clientId = $token->client_id
            ?: config_dynamic('bitrix24.client_id', config('services.bitrix24.client_id'));
        $clientSecret = $token->client_secret
            ?: config_dynamic('bitrix24.client_secret', config('services.bitrix24.client_secret'));

        if (! $clientId || ! $clientSecret) {
            throw new RuntimeException('BITRIX24_CLIENT_ID and BITRIX24_CLIENT_SECRET are required for token refresh.');
        }

        try {
            $response = $this->httpClient->request('POST', self::OAUTH_TOKEN_URL, [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $token->refresh_token,
                ],
            ]);

            $body = (string) $response->getBody();

            /** @var array<string, mixed>|null $data */
            $data = json_decode($body, true);

            if (! is_array($data) || ! isset($data['access_token'], $data['refresh_token'])) {
                throw new RuntimeException("Invalid OAuth refresh response: {$body}");
            }

            $token->update([
                'access_token' => (string) $data['access_token'],
                'refresh_token' => (string) $data['refresh_token'],
                'expires_at' => now()->addSeconds((int) ($data['expires_in'] ?? 3600)),
            ]);

            $this->logTokenEvent('token_refresh_success', [
                'domain' => $token->domain,
                'expires_at' => $token->expires_at?->toIso8601String(),
            ]);

            Log::channel('webhook')->info('Bitrix24 OAuth token refreshed', [
                'domain' => $token->domain,
            ]);
        } catch (Throwable $e) {
            $this->logTokenEvent('token_refresh_failed', [
                'domain' => $token->domain ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            Log::channel('webhook')->error('Bitrix24 OAuth token refresh failed', [
                'domain' => $token->domain ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException("Bitrix24 token refresh failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Stores initial tokens received during app installation.
     *
     * @param  array<string, mixed>  $authData
     */
    public function storeTokensFromInstall(array $authData): Bitrix24Token
    {
        $domain = (string) ($authData['domain'] ?? config_dynamic('bitrix24.domain', config('services.bitrix24.domain', '')));

        $token = Bitrix24Token::updateOrCreate(
            ['domain' => $domain],
            [
                'access_token' => (string) ($authData['access_token'] ?? ''),
                'refresh_token' => (string) ($authData['refresh_token'] ?? ''),
                'expires_at' => now()->addSeconds((int) ($authData['expires_in'] ?? 3600)),
                'application_token' => (string) ($authData['application_token'] ?? ''),
                'client_id' => (string) config_dynamic('bitrix24.client_id', config('services.bitrix24.client_id', '')),
                'client_secret' => (string) config_dynamic('bitrix24.client_secret', config('services.bitrix24.client_secret', '')),
            ],
        );

        $this->logTokenEvent('token_install', [
            'domain' => $domain,
            'expires_at' => $token->expires_at?->toIso8601String(),
        ]);

        return $token;
    }

    private function logTokenEvent(string $event, array $context): void
    {
        try {
            WebhookLog::create([
                'direction' => WebhookLog::DIRECTION_BITRIX_TO_BOTMAKER,
                'source_event' => $event,
                'payload_in' => $context,
                'status' => WebhookLog::STATUS_RECEIVED,
            ]);
        } catch (Throwable) {
            // Avoid breaking the auth flow if logging fails
        }
    }
}
