<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Tests\Fakes;

use Closure;
use Datascaled\LaravelM365Mailer\Contracts\GraphMailClient;
use Datascaled\LaravelM365Mailer\Support\M365TransportConfig;

final class RecordingGraphMailClient implements GraphMailClient
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $calls = [];

    public function __construct(private readonly ?Closure $handler = null) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendMail(M365TransportConfig $config, string $accessToken, string $sender, array $payload): array
    {
        $this->calls[] = [
            'config' => $config,
            'access_token' => $accessToken,
            'sender' => $sender,
            'payload' => $payload,
        ];

        if ($this->handler instanceof Closure) {
            return ($this->handler)($config, $accessToken, $sender, $payload);
        }

        return [
            'endpoint' => '/users/'.rawurlencode($sender).'/sendMail',
            'status' => 202,
            'headers' => ['x-ms-request-id' => ['test-request-id']],
            'body' => [],
        ];
    }
}
