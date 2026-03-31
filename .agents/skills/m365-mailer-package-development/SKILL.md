# Skill: M365 Mailer Package Development

## When to use
Use this skill when implementing or changing behavior in the `datascaled/laravel-m365-mailer` package.

## Core invariants
1. Transport registration must stay available as `mail.mailers.*.transport = m365`.
2. Logging off means no writes to `m365_mail_messages` and `m365_mail_events`.
3. Logging on + required DB missing must fail before Graph send.
4. Post-send logging errors must not trigger rethrow/retry loops.
5. GDPR defaults: recipient masking and hashing; plaintext recipients only with explicit opt-in.

## Implementation checklist
1. Confirm the change location (`Transport`, `Logging`, `Graph`, `Config`, `Command`, `Tests`).
2. Update config contract if needed (`config/m365-mailer.php`) and document it in README.
3. Keep status lifecycle consistent: `queued`, `sending`, `sent`, `failed`.
4. If persistence changes, update both migration stubs and tests.
5. Add or update tests for happy path, failure path, and policy edge case.
6. Run formatting, tests, and static analysis before finishing.

## Validation
- `composer format`
- `composer test`
- `composer analyse`

## Common pitfalls
- Throwing after successful Graph send due to logging write failure.
- Accidentally writing logs while `logging.enabled=false`.
- Breaking mailer registration by changing provider binding/extension sequence.
