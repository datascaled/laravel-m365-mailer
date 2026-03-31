<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Transport;

use Datascaled\LaravelM365Mailer\Contracts\AccessTokenProvider;
use Datascaled\LaravelM365Mailer\Contracts\GraphMailClient;
use Datascaled\LaravelM365Mailer\Logging\DatabaseMailLogger;
use Datascaled\LaravelM365Mailer\Logging\RecipientSanitizer;
use Datascaled\LaravelM365Mailer\Support\GraphMessageBuilder;
use Datascaled\LaravelM365Mailer\Support\M365TransportConfig;
use Datascaled\LaravelM365Mailer\Support\MessageAttemptResolver;
use Illuminate\Contracts\Container\Container;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class M365TransportFactory
{
    public function __construct(
        private readonly Container $container,
        private readonly AccessTokenProvider $tokenProvider,
        private readonly GraphMailClient $graphClient,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $mailerConfig
     */
    public function make(array $mailerConfig): TransportInterface
    {
        $packageConfig = (array) config('m365-mailer', []);
        $transportConfig = M365TransportConfig::fromArray($mailerConfig, $packageConfig);

        $mailLogger = new DatabaseMailLogger(
            $transportConfig,
            new RecipientSanitizer($transportConfig->recipientHashKey),
            $this->logger,
        );

        return new M365Transport(
            config: $transportConfig,
            tokenProvider: $this->tokenProvider,
            graphClient: $this->graphClient,
            mailLogger: $mailLogger,
            messageBuilder: new GraphMessageBuilder,
            attemptResolver: new MessageAttemptResolver($this->container),
            appLogger: $this->logger,
        );
    }
}
