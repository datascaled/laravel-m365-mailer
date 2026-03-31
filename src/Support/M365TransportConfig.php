<?php

declare(strict_types=1);

namespace Datascaled\LaravelM365Mailer\Support;

use InvalidArgumentException;

final class M365TransportConfig
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly string $sender,
        public readonly int $timeout,
        public readonly bool $saveToSentItems,
        public readonly bool $loggingEnabled,
        public readonly int $loggingRetentionDays,
        public readonly bool $storeRecipientsPlaintext,
        public readonly bool $requireDatabase,
        public readonly string $recipientHashKey,
        public readonly ?string $cacheStore,
    ) {}

    /**
     * @param  array<string, mixed>  $mailerConfig
     * @param  array<string, mixed>  $packageConfig
     */
    public static function fromArray(array $mailerConfig, array $packageConfig): self
    {
        $loggingConfig = is_array($packageConfig['logging'] ?? null)
            ? $packageConfig['logging']
            : [];

        $tenantId = self::requiredString($mailerConfig, 'tenant_id');
        $clientId = self::requiredString($mailerConfig, 'client_id');
        $clientSecret = self::requiredString($mailerConfig, 'client_secret');

        $sender = trim((string) ($mailerConfig['sender'] ?? ''));

        if ($sender === '') {
            throw new InvalidArgumentException('M365 mailer config requires a non-empty "sender" value.');
        }

        $timeout = max(1, (int) ($mailerConfig['timeout'] ?? $packageConfig['timeout'] ?? 15));
        $saveToSentItems = (bool) ($mailerConfig['save_to_sent_items'] ?? $packageConfig['save_to_sent_items'] ?? true);

        $loggingEnabled = (bool) ($loggingConfig['enabled'] ?? false);
        $loggingRetentionDays = max(1, (int) ($loggingConfig['retention_days'] ?? 30));
        $storeRecipientsPlaintext = (bool) ($loggingConfig['store_recipients_plaintext'] ?? false);
        $requireDatabase = (bool) ($loggingConfig['require_database'] ?? true);
        $recipientHashKey = (string) ($loggingConfig['recipient_hash_key'] ?? config('app.key') ?? 'm365-mailer-default-key');
        $cacheStore = isset($packageConfig['cache_store']) ? (string) $packageConfig['cache_store'] : null;

        return new self(
            tenantId: $tenantId,
            clientId: $clientId,
            clientSecret: $clientSecret,
            sender: $sender,
            timeout: $timeout,
            saveToSentItems: $saveToSentItems,
            loggingEnabled: $loggingEnabled,
            loggingRetentionDays: $loggingRetentionDays,
            storeRecipientsPlaintext: $storeRecipientsPlaintext,
            requireDatabase: $requireDatabase,
            recipientHashKey: $recipientHashKey,
            cacheStore: $cacheStore,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function requiredString(array $config, string $key): string
    {
        $value = trim((string) ($config[$key] ?? ''));

        if ($value === '') {
            throw new InvalidArgumentException(sprintf('M365 mailer config requires "%s".', $key));
        }

        return $value;
    }
}
