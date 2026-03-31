<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Enums;

enum M365MailStatus: string
{
    case QUEUED = 'queued';
    case SENDING = 'sending';
    case SENT = 'sent';
    case FAILED = 'failed';
}
