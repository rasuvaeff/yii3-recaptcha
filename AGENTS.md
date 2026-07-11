# AGENTS.md — yii3-recaptcha

Guidance for AI agents working on this package. Read before changing code.

## What this is

A Google reCAPTCHA v2/v3 integration for Yii3 (PHP 8.3+). Provides `RecaptchaV2`
and `RecaptchaV3` widgets and `RecaptchaV2Field`/`RecaptchaV3Field`
(`yiisoft/form-model`) fields for rendering challenges, and
`RecaptchaV2Rule`/`RecaptchaV3Rule` with their handlers for server-side verification
via the Yii validator pipeline. In a headless setup (SPA/API) only the validator
half is used. HTTP calls go through any PSR-18 client.

Public API (namespace `Rasuvaeff\Yii3Recaptcha\`):

- `RecaptchaV2`, `RecaptchaV3` — widgets (`withNonce()` for CSP)
- `Field\RecaptchaV2Field`, `Field\RecaptchaV3Field` — form-model fields (delegate to widgets)
- `RecaptchaConfig` — immutable configuration DTO
- `RecaptchaClient` — PSR-18 siteverify client (fail-closed, never throws)
- `VerificationResult` — response DTO (`?float $score` for v3; `transportError()`/`isTransportError()`)
- `RecaptchaV2Rule` / `RecaptchaV2RuleHandler`, `RecaptchaV3Rule` / `RecaptchaV3RuleHandler` — validator pairs (both accept `failOpenOnError`)
- `ClientIpResolverInterface` + `RemoteAddrClientIpResolver` — pluggable client-IP resolution
- `RecaptchaRegistry` — static fallback for no-arg handler construction (populated by bootstrap)
- `Exception\RecaptchaException` (marker), `Exception\MissingSiteKeyException`, `Exception\MissingClientException`
- `RecaptchaV2Theme`, `RecaptchaV2Type`, `RecaptchaV2Size` — backed string enums
- `AbstractRecaptchaRuleHandler` — `@internal` shared handler plumbing (client, IP resolver, translate)

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
- `RecaptchaClient`, `RecaptchaConfig`, `VerificationResult`, resolvers are
  `final readonly class`. Handlers are `final readonly` extending
  `abstract readonly AbstractRecaptchaRuleHandler` (shared client/IP/translate).
- **Hybrid dep resolution.** Handler ctor deps (`RecaptchaClient`,
  `ClientIpResolverInterface`, translator) are **optional**; when absent they
  fall back to `RecaptchaRegistry`, which `config/bootstrap.php` populates from
  the container. This makes the handlers work with the `yiisoft/validator`
  default `SimpleRuleHandlerContainer` (no-arg `new`) out of the box AND with a
  container-backed resolver (injected deps win). Do not make the deps required
  again — that breaks the no-config path. When neither injected nor registered,
  `client()` throws `MissingClientException`. `config/di.php` and
  `config/bootstrap.php` are guarded by `ConfigWiringTest` (real
  `Yiisoft\Di\Container`), since they are not covered by cs/psalm/testo. The app
  must still provide PSR-18 `ClientInterface` + PSR-17 factories.
- `RecaptchaClient::verify()` uses `secretV2`, `verifyV3()` uses `secretV3`;
  `verifyWithSecret()` accepts a custom secret. **Never throws** — transport/HTTP/
  JSON failures return `VerificationResult::transportError()` (fail-closed).
- v3 handler logic: `success && score >= threshold && (action === null || action === response.action)`.
- Fail-open is per-rule opt-in (`failOpenOnError`) and applies **only** to
  transport errors (`isTransportError()`), never to a genuine `success:false` verdict.
- When `sendRemoteIp` is set, the client IP comes from `ClientIpResolverInterface`.
  The default `RemoteAddrClientIpResolver` reads `REMOTE_ADDR` validated with
  `FILTER_VALIDATE_IP`; behind a proxy the app rebinds the interface.
- `Field\Recaptcha*Field` (form-model) **delegate** to the widgets — keep a single
  rendering path; never duplicate widget markup into the fields.
- `rasuvaeff/property-testing` covers algebraic invariants only: v3 score/threshold
  monotonicity and widget inline-JS XSS-safety. `mbstring` is required in every CI
  job (build + static-analysis) or CI reds while local stays green.
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
