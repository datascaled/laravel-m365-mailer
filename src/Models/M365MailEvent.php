<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Models;

use Illuminate\Database\Eloquent\Model;

class M365MailEvent extends Model
{
    protected $table = 'm365_mail_events';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
