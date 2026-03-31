<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Models;

use Illuminate\Database\Eloquent\Model;

class M365MailMessage extends Model
{
    protected $table = 'm365_mail_messages';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'to_recipients' => 'array',
            'cc_recipients' => 'array',
            'bcc_recipients' => 'array',
            'debug_context' => 'array',
            'queued_at' => 'datetime',
            'sending_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
