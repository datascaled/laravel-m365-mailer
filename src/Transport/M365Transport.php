<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Transport;

use Datascaled\LaravelM365Mailer\Contracts\AccessTokenProvider;
use Datascaled\LaravelM365Mailer\Contracts\GraphMailClient;
use Datascaled\LaravelM365Mailer\Contracts\MailLogger;
use Datascaled\LaravelM365Mailer\Support\GraphMessageBuilder;
use Datascaled\LaravelM365Mailer\Support\M365TransportConfig;
use Datascaled\LaravelM365Mailer\Support\MessageAttemptResolver;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;
use Throwable;

final class M365Transport extends AbstractTransport
{
    public function __construct(
        private readonly M365TransportConfig $config,
        private readonly AccessTokenProvider $tokenProvider,
        private readonly GraphMailClient $graphClient,
        private readonly MailLogger $mailLogger,
        private readonly GraphMessageBuilder $messageBuilder,
        private readonly MessageAttemptResolver $attemptResolver,
        private readonly LoggerInterface $appLogger,
    ) {
        parent::__construct(logger: $appLogger);
    }

    protected function doSend(SentMessage $message): void
    {
        $originalMessage = $message->getOriginalMessage();

        if (! $originalMessage instanceof Message) {
            throw new TransportException('M365 transport only supports Symfony Message instances.');
        }

        $email = MessageConverter::toEmail($originalMessage);
        $attempt = $this->attemptResolver->resolve();
        $sender = $this->resolveSender();
        $messageUuid = (string) Str::uuid();
        $symfonyMessageId = $message->getMessageId();

        $this->mailLogger->logQueued($messageUuid, $symfonyMessageId, $attempt, $sender, $email);
        $this->mailLogger->logSending($messageUuid, $attempt, ['sender' => $sender]);

        try {
            $accessToken = $this->tokenProvider->getAccessToken($this->config);
            $payload = $this->messageBuilder->buildPayload($email, $sender, $this->config->saveToSentItems);
            $result = $this->graphClient->sendMail($this->config, $accessToken, $sender, $payload);
        } catch (Throwable $throwable) {
            $this->tryLogFailed($messageUuid, $attempt, $throwable, ['phase' => 'send']);

            throw $this->toTransportException($throwable);
        }

        try {
            $this->mailLogger->logSent($messageUuid, $attempt, [
                'phase' => 'post_send',
                'graph_status' => $result['status'] ?? null,
                'graph_endpoint' => $result['endpoint'] ?? null,
                'graph_headers' => $result['headers'] ?? [],
                'graph_body' => $result['body'] ?? [],
            ]);
        } catch (Throwable $loggingFailure) {
            // Send already succeeded; avoid rethrowing to prevent duplicate sends on queue retries.
            $this->appLogger->warning('M365 send succeeded but post-send logging failed.', [
                'message_uuid' => $messageUuid,
                'exception' => $loggingFailure::class,
                'message' => $loggingFailure->getMessage(),
            ]);
        }
    }

    public function __toString(): string
    {
        return 'm365';
    }

    private function resolveSender(): string
    {
        return $this->config->sender;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function tryLogFailed(string $messageUuid, int $attempt, Throwable $throwable, array $context): void
    {
        try {
            $this->mailLogger->logFailed($messageUuid, $attempt, $throwable, $context);
        } catch (Throwable $loggingFailure) {
            $this->appLogger->error('M365 pre-send failure could not be logged.', [
                'message_uuid' => $messageUuid,
                'exception' => $loggingFailure::class,
                'message' => $loggingFailure->getMessage(),
            ]);
        }
    }

    private function toTransportException(Throwable $throwable): TransportExceptionInterface
    {
        if ($throwable instanceof TransportExceptionInterface) {
            return $throwable;
        }

        return new TransportException(
            sprintf('M365 transport failed: %s', $throwable->getMessage()),
            (int) $throwable->getCode(),
            $throwable,
        );
    }
}
