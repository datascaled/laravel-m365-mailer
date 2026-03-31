<?php

declare(strict_types=1);

use Illuminate\Support\Env;

return [
    /*
    |--------------------------------------------------------------------------
    | Base Settings
    |--------------------------------------------------------------------------
    |
    | These values act as defaults for all M365 transports and can be
    | overridden per-mailer in `config/mail.php`.
    |
    */
    'timeout' => Env::get('M365_MAIL_TIMEOUT', 15),

    'save_to_sent_items' => Env::get('M365_MAIL_SAVE_TO_SENT_ITEMS', true),

    'cache_store' => Env::get('M365_MAIL_CACHE_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Logging is disabled by default so that installation stays minimal.
    | Enable this only after publishing and running the package migrations.
    |
    */
    'logging' => [
        'enabled' => Env::get('M365_MAIL_LOGGING_ENABLED', false),

        'retention_days' => Env::get('M365_MAIL_LOGGING_RETENTION_DAYS', 30),

        'store_recipients_plaintext' => Env::get('M365_MAIL_LOGGING_STORE_RECIPIENTS_PLAINTEXT', false),

        'require_database' => Env::get('M365_MAIL_LOGGING_REQUIRE_DATABASE', true),

        'recipient_hash_key' => Env::get('M365_MAIL_LOGGING_RECIPIENT_HASH_KEY', Env::get('APP_KEY', 'm365-mailer-default-key')),
    ],
];
