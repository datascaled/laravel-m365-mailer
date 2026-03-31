<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Tests\Fakes;

use Datascaled\LaravelM365Mailer\Contracts\AccessTokenProvider;
use Datascaled\LaravelM365Mailer\Support\M365TransportConfig;

final class FakeAccessTokenProvider implements AccessTokenProvider
{
    public function getAccessToken(M365TransportConfig $config): string
    {
        return 'fake-access-token';
    }
}
