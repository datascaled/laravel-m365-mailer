<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Contracts;

use Datascaled\LaravelM365Mailer\Support\M365TransportConfig;

interface AccessTokenProvider
{
    public function getAccessToken(M365TransportConfig $config): string;
}
