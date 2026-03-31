<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Commands;

use Datascaled\LaravelM365Mailer\Models\M365MailEvent;
use Datascaled\LaravelM365Mailer\Models\M365MailMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class PruneM365MailLogsCommand extends Command
{
    protected $signature = 'm365-mail:prune {--days= : Override retention in days}';

    protected $description = 'Delete old M365 mail log rows based on retention policy.';

    public function handle(): int
    {
        if (! Schema::hasTable('m365_mail_messages') || ! Schema::hasTable('m365_mail_events')) {
            $this->warn('M365 log tables are missing. Nothing to prune.');

            return self::SUCCESS;
        }

        $days = max(1, (int) ($this->option('days') ?: config('m365-mailer.logging.retention_days', 30)));
        $cutoff = now()->subDays($days);

        $eventsDeleted = M365MailEvent::query()->where('created_at', '<', $cutoff)->delete();
        $messagesDeleted = M365MailMessage::query()->where('created_at', '<', $cutoff)->delete();

        $this->info(sprintf(
            'Pruned %d message rows and %d event rows older than %s.',
            $messagesDeleted,
            $eventsDeleted,
            $cutoff->toDateTimeString(),
        ));

        return self::SUCCESS;
    }
}
