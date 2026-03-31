# Laravel M365 Mailer

A Laravel mail transport driver for Microsoft 365 / Microsoft Graph with optional GDPR-conscious database logging.

## Features

- Full Laravel mailer transport (`transport: m365`) for Mailables, Notifications, and queued mails.
- Client-credentials flow (`tenant_id`, `client_id`, `client_secret`).
- Optional DB-backed message status history (`queued`, `sending`, `sent`, `failed`).
- GDPR-oriented defaults: recipient masking + hashing; plaintext recipient storage is opt-in.
- Built-in retention cleanup command: `m365-mail:prune`.

## Requirements

- PHP 8.3+
- Laravel 11 or 12

## Installation

```bash
composer require datascaled/laravel-m365-mailer
```

## Quick Start (No DB Logging)

1. Configure an `m365` mailer in `config/mail.php`.

```php
'mailers' => [
    // ...
    'm365' => [
        'transport' => 'm365',
        'tenant_id' => env('M365_TENANT_ID'),
        'client_id' => env('M365_CLIENT_ID'),
        'client_secret' => env('M365_CLIENT_SECRET'),
        'timeout' => env('M365_MAIL_TIMEOUT', 15),
    ],
],
```

2. Use it as default mailer or explicitly when sending:

```php
Mail::mailer('m365')->to('user@example.com')->send(new WelcomeMail());
```

With default config, package logging is disabled and no package-specific DB writes are made.

## Advanced Setup (Enable DB Logging)

1. Publish package config and migrations:

```bash
php artisan vendor:publish --tag="m365-mailer-config"
php artisan vendor:publish --tag="m365-mailer-migrations"
php artisan migrate
```

2. Enable logging in `config/m365-mailer.php` or `.env`:

```env
M365_MAIL_LOGGING_ENABLED=true
M365_MAIL_LOGGING_REQUIRE_DATABASE=true
M365_MAIL_LOGGING_RETENTION_DAYS=30
M365_MAIL_LOGGING_STORE_RECIPIENTS_PLAINTEXT=false
```

## Configuration Reference

`config/m365-mailer.php` contains:

- `timeout`
- `save_to_sent_items`
- `cache_store`
- `logging.enabled`
- `logging.retention_days`
- `logging.store_recipients_plaintext`
- `logging.require_database`
- `logging.recipient_hash_key`

Per-mailer credentials remain in `config/mail.php` under `mail.mailers.m365`.
The sender is always taken from `mail.from.address` (`MAIL_FROM_ADDRESS`).

## Logging Behavior

- `logging.enabled = false`: package performs no DB logging.
- `logging.enabled = true` + `require_database = true`: missing tables cause a hard fail before sending.
- If Graph accepts the message but post-send DB logging fails, sending is not rethrown (prevents duplicate deliveries on queue retry).

## Retention / Pruning

Delete old log data:

```bash
php artisan m365-mail:prune
```

Override retention window:

```bash
php artisan m365-mail:prune --days=14
```

## Testing

```bash
composer test
```

## License

MIT
