<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer;

use Datascaled\LaravelM365Mailer\Commands\PruneM365MailLogsCommand;
use Datascaled\LaravelM365Mailer\Contracts\AccessTokenProvider;
use Datascaled\LaravelM365Mailer\Contracts\GraphMailClient;
use Datascaled\LaravelM365Mailer\Graph\GraphSdkMailClient;
use Datascaled\LaravelM365Mailer\Token\ClientCredentialsTokenProvider;
use Datascaled\LaravelM365Mailer\Transport\M365TransportFactory;
use Illuminate\Mail\MailManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelM365MailerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-m365-mailer')
            ->hasConfigFile('m365-mailer')
            ->hasMigrations([
                'create_m365_mail_messages_table',
                'create_m365_mail_events_table',
            ])
            ->hasCommand(PruneM365MailLogsCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->bind(GraphMailClient::class, GraphSdkMailClient::class);
        $this->app->bind(AccessTokenProvider::class, ClientCredentialsTokenProvider::class);
        $this->app->singleton(M365TransportFactory::class);
    }

    public function bootingPackage()
    {
        $this->app->afterResolving(MailManager::class, function (MailManager $mailManager): void {
            $mailManager->extend('m365', function (array $config) {
                return $this->app->make(M365TransportFactory::class)->make($config);
            });
        });
    }
}
