<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Contracts;

use Datascaled\LaravelM365Mailer\Support\M365TransportConfig;

interface GraphMailClient
{
    /**
     * @return array<string, mixed>
     */
    public function sendMail(M365TransportConfig $config, string $accessToken, string $sender, array $payload): array;
}
