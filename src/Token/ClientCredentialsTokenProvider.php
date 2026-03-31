<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Token;

use Datascaled\LaravelM365Mailer\Contracts\AccessTokenProvider;
use Datascaled\LaravelM365Mailer\Support\M365TransportConfig;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

final class ClientCredentialsTokenProvider implements AccessTokenProvider
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly CacheFactory $cache,
    ) {}

    public function getAccessToken(M365TransportConfig $config): string
    {
        $cacheKey = sprintf('m365-mailer:token:%s:%s', $config->tenantId, $config->clientId);
        $cacheStore = $this->resolveCacheStore($config);

        /** @var array{access_token?: string, expires_at?: int}|null $cached */
        $cached = $cacheStore->get($cacheKey);

        if (is_array($cached)) {
            $accessToken = (string) ($cached['access_token'] ?? '');
            $expiresAt = (int) ($cached['expires_at'] ?? 0);

            if ($accessToken !== '' && $expiresAt > (time() + 30)) {
                return $accessToken;
            }
        }

        $response = $this->http
            ->asForm()
            ->timeout($config->timeout)
            ->post(
                sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $config->tenantId),
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => $config->clientId,
                    'client_secret' => $config->clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                ]
            );

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Unable to retrieve Microsoft Graph token. HTTP %s: %s',
                $response->status(),
                $response->body()
            ));
        }

        $accessToken = trim((string) $response->json('access_token'));

        if ($accessToken === '') {
            throw new RuntimeException('Microsoft Graph token response did not include an access token.');
        }

        $expiresIn = max(120, (int) $response->json('expires_in', 3600));
        $expiresAt = time() + $expiresIn;

        $cacheStore->put($cacheKey, [
            'access_token' => $accessToken,
            'expires_at' => $expiresAt,
        ], max(60, $expiresIn - 60));

        return $accessToken;
    }

    private function resolveCacheStore(M365TransportConfig $config): Repository
    {
        if ($config->cacheStore === null || $config->cacheStore === '') {
            return $this->cache->store();
        }

        return $this->cache->store($config->cacheStore);
    }
}
