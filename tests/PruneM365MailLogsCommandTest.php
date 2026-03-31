<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('prunes only rows older than retention window', function (): void {
    $this->createLogTables();

    DB::table('m365_mail_messages')->insert([
        [
            'message_uuid' => '11111111-1111-1111-1111-111111111111',
            'symfony_message_id' => 'old-message',
            'status' => 'sent',
            'attempt' => 1,
            'sender' => 'sender@example.com',
            'subject' => 'Old',
            'to_recipients' => json_encode([], JSON_THROW_ON_ERROR),
            'cc_recipients' => json_encode([], JSON_THROW_ON_ERROR),
            'bcc_recipients' => json_encode([], JSON_THROW_ON_ERROR),
            'queued_at' => now()->subDays(40),
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ],
        [
            'message_uuid' => '22222222-2222-2222-2222-222222222222',
            'symfony_message_id' => 'new-message',
            'status' => 'sent',
            'attempt' => 1,
            'sender' => 'sender@example.com',
            'subject' => 'New',
            'to_recipients' => json_encode([], JSON_THROW_ON_ERROR),
            'cc_recipients' => json_encode([], JSON_THROW_ON_ERROR),
            'bcc_recipients' => json_encode([], JSON_THROW_ON_ERROR),
            'queued_at' => now()->subDays(3),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ],
    ]);

    DB::table('m365_mail_events')->insert([
        [
            'message_uuid' => '11111111-1111-1111-1111-111111111111',
            'status' => 'sent',
            'attempt' => 1,
            'context' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => now()->subDays(40),
        ],
        [
            'message_uuid' => '22222222-2222-2222-2222-222222222222',
            'status' => 'sent',
            'attempt' => 1,
            'context' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => now()->subDays(3),
        ],
    ]);

    $this->artisan('m365-mail:prune --days=30')->assertSuccessful();

    expect(DB::table('m365_mail_messages')->pluck('message_uuid')->all())
        ->toBe(['22222222-2222-2222-2222-222222222222']);

    expect(DB::table('m365_mail_events')->pluck('message_uuid')->all())
        ->toBe(['22222222-2222-2222-2222-222222222222']);
});
