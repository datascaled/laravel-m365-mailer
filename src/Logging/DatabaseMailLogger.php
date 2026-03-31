<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Logging;

use Datascaled\LaravelM365Mailer\Contracts\MailLogger;
use Datascaled\LaravelM365Mailer\Enums\M365MailStatus;
use Datascaled\LaravelM365Mailer\Exceptions\LoggingInfrastructureException;
use Datascaled\LaravelM365Mailer\Models\M365MailEvent;
use Datascaled\LaravelM365Mailer\Models\M365MailMessage;
use Datascaled\LaravelM365Mailer\Support\M365TransportConfig;
use Illuminate\Support\Facades\Schema;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Throwable;

final class DatabaseMailLogger implements MailLogger
{
    private ?bool $tablesAvailable = null;

    public function __construct(
        private readonly M365TransportConfig $config,
        private readonly RecipientSanitizer $recipientSanitizer,
        private readonly LoggerInterface $logger,
    ) {}

    public function logQueued(string $messageUuid, string $symfonyMessageId, int $attempt, string $sender, Email $email): void
    {
        if (! $this->config->loggingEnabled) {
            return;
        }

        $this->ensureInfrastructure();

        $now = now();

        M365MailMessage::query()->create([
            'message_uuid' => $messageUuid,
            'symfony_message_id' => $symfonyMessageId,
            'status' => M365MailStatus::QUEUED->value,
            'attempt' => $attempt,
            'sender' => $sender,
            'subject' => $email->getSubject(),
            'to_recipients' => $this->recipientSanitizer->sanitize($email->getTo(), $this->config->storeRecipientsPlaintext),
            'cc_recipients' => $this->recipientSanitizer->sanitize($email->getCc(), $this->config->storeRecipientsPlaintext),
            'bcc_recipients' => $this->recipientSanitizer->sanitize($email->getBcc(), $this->config->storeRecipientsPlaintext),
            'queued_at' => $now,
            'debug_context' => [
                'subject_length' => strlen((string) $email->getSubject()),
            ],
        ]);

        $this->createEvent($messageUuid, M365MailStatus::QUEUED, $attempt, [
            'sender' => $sender,
            'symfony_message_id' => $symfonyMessageId,
        ]);
    }

    public function logSending(string $messageUuid, int $attempt, array $context = []): void
    {
        if (! $this->config->loggingEnabled) {
            return;
        }

        $this->ensureInfrastructure();

        M365MailMessage::query()
            ->where('message_uuid', $messageUuid)
            ->update([
                'status' => M365MailStatus::SENDING->value,
                'attempt' => $attempt,
                'sending_at' => now(),
            ]);

        $this->createEvent($messageUuid, M365MailStatus::SENDING, $attempt, $context);
    }

    public function logSent(string $messageUuid, int $attempt, array $context = []): void
    {
        if (! $this->config->loggingEnabled) {
            return;
        }

        $this->ensureInfrastructure();

        M365MailMessage::query()
            ->where('message_uuid', $messageUuid)
            ->update([
                'status' => M365MailStatus::SENT->value,
                'attempt' => $attempt,
                'sent_at' => now(),
                'error_code' => null,
                'error_message' => null,
                'debug_context' => $context,
            ]);

        $this->createEvent($messageUuid, M365MailStatus::SENT, $attempt, $context);
    }

    public function logFailed(string $messageUuid, int $attempt, Throwable $throwable, array $context = []): void
    {
        if (! $this->config->loggingEnabled) {
            return;
        }

        $this->ensureInfrastructure();

        M365MailMessage::query()
            ->where('message_uuid', $messageUuid)
            ->update([
                'status' => M365MailStatus::FAILED->value,
                'attempt' => $attempt,
                'failed_at' => now(),
                'error_code' => (string) $throwable->getCode(),
                'error_message' => $throwable->getMessage(),
                'debug_context' => $context,
            ]);

        $this->createEvent($messageUuid, M365MailStatus::FAILED, $attempt, array_merge($context, [
            'exception' => get_class($throwable),
            'message' => $throwable->getMessage(),
            'code' => $throwable->getCode(),
        ]), (string) $throwable->getCode(), $throwable->getMessage());
    }

    private function ensureInfrastructure(): void
    {
        if ($this->tablesAvailable !== null) {
            if (! $this->tablesAvailable && $this->config->requireDatabase) {
                throw new LoggingInfrastructureException('M365 logging tables are unavailable.');
            }

            return;
        }

        $messagesTableExists = Schema::hasTable('m365_mail_messages');
        $eventsTableExists = Schema::hasTable('m365_mail_events');
        $this->tablesAvailable = $messagesTableExists && $eventsTableExists;

        if ($this->tablesAvailable) {
            return;
        }

        $message = 'M365 logging is enabled, but required tables are missing. Run package migrations.';

        if ($this->config->requireDatabase) {
            throw new LoggingInfrastructureException($message);
        }

        $this->logger->warning($message, [
            'messages_table_exists' => $messagesTableExists,
            'events_table_exists' => $eventsTableExists,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function createEvent(
        string $messageUuid,
        M365MailStatus $status,
        int $attempt,
        array $context = [],
        ?string $errorCode = null,
        ?string $errorMessage = null,
    ): void {
        if (! $this->tablesAvailable) {
            return;
        }

        M365MailEvent::query()->create([
            'message_uuid' => $messageUuid,
            'status' => $status->value,
            'attempt' => $attempt,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'context' => $context,
            'created_at' => now(),
        ]);
    }
}
