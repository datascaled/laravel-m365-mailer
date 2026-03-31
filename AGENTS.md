# Project Context for Codex Agents

## Overview
- Project: `datascaled/laravel-m365-mailer`
- Type: Laravel package (Composer)
- Purpose: Provide a production-ready Laravel `m365` mail transport via Microsoft Graph with optional GDPR-conscious logging.
- Supported runtime: PHP 8.3+, Laravel 11/12

## Package Guarantees (Must Keep)
- `logging.enabled = false` must mean **no package-specific DB writes**.
- If logging is enabled and DB logging is required but tables are missing, fail **before** sending.
- If Graph already accepted the mail and post-send logging fails, do **not** rethrow (avoid duplicate sends on queue retries).
- Default recipient logging must be masked + hashed. Plaintext recipient storage is opt-in only.

## Architecture Map
- Service provider: `src/LaravelM365MailerServiceProvider.php`
- Transport: `src/Transport/M365Transport.php`
- Transport factory: `src/Transport/M365TransportFactory.php`
- Graph client + token provider: `src/Graph/*`, `src/Token/*`
- Logging: `src/Logging/*`, `src/Models/*`, `src/Enums/*`
- Config: `config/m365-mailer.php`
- Migrations: `database/migrations/*.stub`
- Command: `src/Commands/PruneM365MailLogsCommand.php`
- Tests: `tests/*`

## Working Rules
- Keep package behavior framework-native (MailManager extension, Symfony transport contracts).
- Avoid introducing new runtime dependencies unless clearly required.
- Keep all environment access inside config files.
- Preserve strict typing and clear exception mapping to transport exceptions.
- Prefer focused Pest feature tests for behavior changes.

## Validation Commands
- Install deps: `composer install`
- Run tests: `composer test`
- Static analysis: `composer analyse`
- Format code: `composer format`

## Local Skills
- `m365-mailer-package-development`: `.agents/skills/m365-mailer-package-development/SKILL.md`
