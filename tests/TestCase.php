<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Tests;

use Closure;
use Datascaled\LaravelM365Mailer\Contracts\AccessTokenProvider;
use Datascaled\LaravelM365Mailer\Contracts\GraphMailClient;
use Datascaled\LaravelM365Mailer\LaravelM365MailerServiceProvider;
use Datascaled\LaravelM365Mailer\Tests\Fakes\FakeAccessTokenProvider;
use Datascaled\LaravelM365Mailer\Tests\Fakes\RecordingGraphMailClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelM365MailerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('mail.default', 'm365');
        $app['config']->set('mail.mailers.m365', [
            'transport' => 'm365',
            'tenant_id' => 'tenant-id',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'sender' => 'sender@example.com',
            'timeout' => 10,
        ]);

        $app['config']->set('m365-mailer.logging.enabled', false);
        $app['config']->set('m365-mailer.logging.retention_days', 30);
        $app['config']->set('m365-mailer.logging.store_recipients_plaintext', false);
        $app['config']->set('m365-mailer.logging.require_database', true);
    }

    protected function createLogTables(): void
    {
        if (! Schema::hasTable('m365_mail_messages')) {
            Schema::create('m365_mail_messages', function (Blueprint $table): void {
                $table->id();
                $table->uuid('message_uuid')->unique();
                $table->string('symfony_message_id')->nullable()->index();
                $table->string('status')->index();
                $table->unsignedInteger('attempt')->default(1);
                $table->string('sender');
                $table->string('subject')->nullable();
                $table->json('to_recipients')->nullable();
                $table->json('cc_recipients')->nullable();
                $table->json('bcc_recipients')->nullable();
                $table->string('error_code')->nullable();
                $table->text('error_message')->nullable();
                $table->json('debug_context')->nullable();
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('sending_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('m365_mail_events')) {
            Schema::create('m365_mail_events', function (Blueprint $table): void {
                $table->id();
                $table->uuid('message_uuid')->index();
                $table->string('status')->index();
                $table->unsignedInteger('attempt')->default(1);
                $table->string('error_code')->nullable();
                $table->text('error_message')->nullable();
                $table->json('context')->nullable();
                $table->timestamp('created_at')->index();
            });
        }
    }

    protected function configureLogging(bool $enabled, bool $requireDatabase = true, bool $storeRecipientsPlaintext = false): void
    {
        config()->set('m365-mailer.logging.enabled', $enabled);
        config()->set('m365-mailer.logging.require_database', $requireDatabase);
        config()->set('m365-mailer.logging.store_recipients_plaintext', $storeRecipientsPlaintext);

        $this->app->make(MailManager::class)->forgetMailers();
    }

    protected function installGraphFakes(?Closure $handler = null): RecordingGraphMailClient
    {
        $graphClient = new RecordingGraphMailClient($handler);

        $this->app->instance(GraphMailClient::class, $graphClient);
        $this->app->instance(AccessTokenProvider::class, new FakeAccessTokenProvider);

        $this->app->make(MailManager::class)->forgetMailers();

        return $graphClient;
    }
}
