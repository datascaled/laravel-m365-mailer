<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Contracts;

use Symfony\Component\Mime\Email;

interface MailLogger
{
    public function logQueued(string $messageUuid, string $symfonyMessageId, int $attempt, string $sender, Email $email): void;

    public function logSending(string $messageUuid, int $attempt, array $context = []): void;

    public function logSent(string $messageUuid, int $attempt, array $context = []): void;

    public function logFailed(string $messageUuid, int $attempt, \Throwable $throwable, array $context = []): void;
}
