<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Graph;

use Datascaled\LaravelM365Mailer\Contracts\GraphMailClient;
use Datascaled\LaravelM365Mailer\Support\M365TransportConfig;
use Microsoft\Graph\Graph;

final class GraphSdkMailClient implements GraphMailClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sendMail(M365TransportConfig $config, string $accessToken, string $sender, array $payload): array
    {
        $endpoint = '/users/'.rawurlencode($sender).'/sendMail';

        $response = (new Graph)
            ->setAccessToken($accessToken)
            ->createRequest('POST', $endpoint)
            ->setTimeout($config->timeout)
            ->attachBody($payload)
            ->execute();

        return [
            'endpoint' => $endpoint,
            'status' => method_exists($response, 'getStatus') ? (int) $response->getStatus() : 202,
            'headers' => method_exists($response, 'getHeaders') ? (array) $response->getHeaders() : [],
            'body' => method_exists($response, 'getBody') ? (array) $response->getBody() : [],
        ];
    }
}
