<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Support;

use Illuminate\Contracts\Container\Container;

final class MessageAttemptResolver
{
    public function __construct(private readonly Container $container) {}

    public function resolve(): int
    {
        if (! $this->container->bound('queue.job')) {
            return 1;
        }

        $job = $this->container->make('queue.job');

        if (! is_object($job) || ! method_exists($job, 'attempts')) {
            return 1;
        }

        return max(1, (int) $job->attempts());
    }
}
