<?php

declare(strict_types=1);

use Datascaled\LaravelM365Mailer\Exceptions\LoggingInfrastructureException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Mailer\Exception\TransportException;

it('sends without logging and does not write package rows', function (): void {
    $this->createLogTables();
    $this->configureLogging(enabled: false);

    $graphClient = $this->installGraphFakes();

    Mail::mailer('m365')->raw('Body', function ($message): void {
        $message->to('alice@example.com')->subject('No logging');
    });

    expect($graphClient->calls)->toHaveCount(1);
    expect(DB::table('m365_mail_messages')->count())->toBe(0);
    expect(DB::table('m365_mail_events')->count())->toBe(0);
});

it('writes status history when logging is enabled', function (): void {
    $this->createLogTables();
    $this->configureLogging(enabled: true, requireDatabase: true, storeRecipientsPlaintext: false);

    $graphClient = $this->installGraphFakes();

    Mail::mailer('m365')->raw('Body', function ($message): void {
        $message->to('alice@example.com', 'Alice')->subject('Logged message');
    });

    expect($graphClient->calls)->toHaveCount(1);

    $storedMessage = DB::table('m365_mail_messages')->first();
    expect($storedMessage)->not()->toBeNull();
    expect($storedMessage->status)->toBe('sent');

    $toRecipients = json_decode((string) $storedMessage->to_recipients, true, flags: JSON_THROW_ON_ERROR);

    expect($toRecipients[0]['display'])->toContain('***');
    expect($toRecipients[0])->not()->toHaveKey('email');
    expect($toRecipients[0])->toHaveKey('hash');

    $statuses = DB::table('m365_mail_events')->orderBy('id')->pluck('status')->all();
    expect($statuses)->toBe(['queued', 'sending', 'sent']);
});

it('hard fails before sending when logging is enabled but tables are missing', function (): void {
    $this->configureLogging(enabled: true, requireDatabase: true, storeRecipientsPlaintext: false);

    $graphClient = $this->installGraphFakes();

    expect(fn () => Mail::mailer('m365')->raw('Body', function ($message): void {
        $message->to('alice@example.com')->subject('Should fail');
    }))->toThrow(LoggingInfrastructureException::class);

    expect($graphClient->calls)->toHaveCount(0);
});

it('logs failed status when graph call fails and rethrows transport exception', function (): void {
    $this->createLogTables();
    $this->configureLogging(enabled: true, requireDatabase: true, storeRecipientsPlaintext: false);

    $this->installGraphFakes(function (): array {
        throw new RuntimeException('Graph temporary failure');
    });

    expect(fn () => Mail::mailer('m365')->raw('Body', function ($message): void {
        $message->to('alice@example.com')->subject('Graph fail');
    }))->toThrow(TransportException::class);

    $storedMessage = DB::table('m365_mail_messages')->first();
    expect($storedMessage->status)->toBe('failed');

    $statuses = DB::table('m365_mail_events')->orderBy('id')->pluck('status')->all();
    expect($statuses)->toBe(['queued', 'sending', 'failed']);
});

it('does not throw when post-send logging fails after successful delivery', function (): void {
    $this->createLogTables();
    $this->configureLogging(enabled: true, requireDatabase: true, storeRecipientsPlaintext: false);

    $graphClient = $this->installGraphFakes(function (): array {
        Schema::drop('m365_mail_events');

        return [
            'endpoint' => '/users/sender%40example.com/sendMail',
            'status' => 202,
            'headers' => [],
            'body' => [],
        ];
    });

    Mail::mailer('m365')->raw('Body', function ($message): void {
        $message->to('alice@example.com')->subject('Post send failure should not rethrow');
    });

    expect($graphClient->calls)->toHaveCount(1);

    $storedMessage = DB::table('m365_mail_messages')->first();
    expect($storedMessage->status)->toBe('sent');
});

it('stores recipient plaintext only when explicitly enabled', function (): void {
    $this->createLogTables();
    $this->configureLogging(enabled: true, requireDatabase: true, storeRecipientsPlaintext: true);

    $this->installGraphFakes();

    Mail::mailer('m365')->raw('Body', function ($message): void {
        $message->to('alice@example.com')->subject('Plaintext opt-in');
    });

    $storedMessage = DB::table('m365_mail_messages')->first();

    $toRecipients = json_decode((string) $storedMessage->to_recipients, true, flags: JSON_THROW_ON_ERROR);

    expect($toRecipients[0]['email'])->toBe('alice@example.com');
    expect($toRecipients[0]['display'])->toContain('***');
});
