# Changelog

All notable changes to `datascaled/laravel-m365-mailer` will be documented in this file.

## 0.1.0 - 2026-03-29

- Added a full Laravel `m365` mail transport based on Microsoft Graph.
- Added client-credentials token provider and Graph SDK mail client.
- Added optional GDPR-conscious DB logging with message/event tables.
- Added hard-fail behavior when logging is enabled but DB tables are missing.
- Added post-send logging fail-safe to prevent duplicate sends on queue retries.
- Added configurable recipient plaintext storage (opt-in).
- Added `m365-mail:prune` command for retention cleanup.
- Added package configuration and migration publishing support.
- Added feature tests for transport behavior, logging modes, failure paths, and prune command.
