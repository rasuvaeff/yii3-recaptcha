# AGENTS.md — yii3-recaptcha

Guidance for AI agents working on this package. Read before changing code.

## What this is

A Google reCAPTCHA v2/v3 integration for Yii3 (PHP 8.3+). Provides `RecaptchaV2`
and `RecaptchaV3` widgets for rendering challenges in forms, and
`RecaptchaV2Rule`/`RecaptchaV3Rule` with their handlers for server-side verification
via the Yii validator pipeline. HTTP calls go through any PSR-18 client.

Public API (namespace `Rasuvaeff\Yii3Recaptcha\`):

- `RecaptchaV2`, `RecaptchaV3` — widgets
- `RecaptchaConfig` — immutable configuration DTO
- `RecaptchaClient` — PSR-18 siteverify client
- `VerificationResult` — verification response DTO (includes `?float $score` for v3)
- `RecaptchaV2Rule` / `RecaptchaV2RuleHandler` — v2 validator pair
- `RecaptchaV3Rule` / `RecaptchaV3RuleHandler` — v3 validator pair (threshold + action check)
- `RecaptchaV2Theme`, `RecaptchaV2Type`, `RecaptchaV2Size` — backed string enums

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **Secret must never leak client-side.** Widgets use siteKey (public);
   secrets are server-side only.
4. **Preserve the public contract.** Update README + tests with any API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
```

Or with Make:

```bash
make build
make cs-fix
make psalm
make test
make test-coverage
make mutation
```

`composer.lock` is gitignored (library).

`make test-coverage` and `make mutation` temporarily install and enable `pcov`
inside the `composer:2` container because the base image has no coverage driver.

## Invariants & gotchas

- `RecaptchaV2`, `RecaptchaV3` widgets are `final class` (not readonly) — they use
  `clone` in `with*` methods per yiisoft/widget convention.
- `RecaptchaV2Rule`, `RecaptchaV3Rule` are `final class` (not readonly) — traits need
  mutable `$skipOnEmpty`.
- `RecaptchaClient`, `RecaptchaConfig`, `VerificationResult`, handlers are
  `final readonly class`.
- `RecaptchaClient::verify()` uses `secretV2`, `verifyV3()` uses `secretV3` from
  config. `verifyWithSecret()` accepts a custom secret for rule-level overrides.
- v3 handler logic: `success && score >= threshold && (action === null || action === response.action)`.
- When `sendRemoteIp` is set, handlers read the client IP from the current request
  via `RequestProviderInterface` (`REMOTE_ADDR`), not from the validation context.
- `RecaptchaV3` widget renders the API script + hidden input + inline script that
  fills the token (on load, or on form submit when `withFormId()` is set — the
  invisible-submit flow). `withBadge(RecaptchaV3Badge::Hidden)` also renders the
  required legal notice.
- Both widgets build inline JS with `json_encode` using XSS-safe flags
  (`JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP`) — never string concat.
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`,
  explicit types.

## When you finish

- Update `README.md` (and `examples/` if usage changed); update `CHANGELOG.md`
  when releasing.
- Re-run `composer build` and paste the output.
