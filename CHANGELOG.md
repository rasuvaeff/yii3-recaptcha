# Changelog

## Unreleased

- Tests build their doubles with `rasuvaeff/understudy-testo` instead of
  hand-written fake classes: `FakeHttpClient`, `FakeRequestProvider`,
  `FakeTranslator`, `FakeRule` and `Support/FixedClientIpResolver` are gone.
  Request capture reads a typed `Arg::captor()`, the never-consulted IP
  resolver and the DI-wired HTTP client are strict doubles that fail at the
  call, and the request-provider call count is a `verify(..., times: 1)`
  claim. Dev-dependency only; the public contract is untouched.

## 2.0.0 — 2026-07-11

### Added

- `RecaptchaV2Field` / `RecaptchaV3Field` — `yiisoft/form-model` fields that bind
  the token to a form model property and delegate rendering to the widgets.
- `ClientIpResolverInterface` + default `RemoteAddrClientIpResolver` — pluggable
  client-IP resolution (rebind behind a proxy/CDN to use the real client IP).
  The default validates the address with `FILTER_VALIDATE_IP`.
- `Exception\RecaptchaException` marker + `Exception\MissingSiteKeyException`.
- `withNonce()` on both widgets — CSP `nonce` on every emitted `<script>`/`<style>`.
- `failOpenOnError` on `RecaptchaV2Rule` / `RecaptchaV3Rule` — opt in to pass
  validation when the siteverify endpoint is unreachable (default is fail-closed).
- `VerificationResult::transportError()` / `isTransportError()` and the
  `TRANSPORT_ERROR` code.

### Changed

- **BREAKING:** `RecaptchaClient` no longer throws on transport/HTTP/JSON errors;
  it returns a failed `VerificationResult` tagged `TRANSPORT_ERROR` (fail-closed).
- **BREAKING:** rule handlers and `RecaptchaRegistry::configure()` now take a
  `ClientIpResolverInterface` where they used to take a `RequestProviderInterface`.
- Handler dependencies stay optional and fall back to `RecaptchaRegistry`
  (populated by the config-plugin bootstrap), so validation works out of the box
  with the default `SimpleRuleHandlerContainer` and with a container-backed
  resolver alike.
- Widgets now throw `MissingSiteKeyException` instead of a plain `RuntimeException`
  when rendered without a site key.

## 1.0.3 — 2026-06-30

- Add `/benchmarks` and `/Makefile` to `.gitattributes` export-ignore.

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.2 — 2026-06-27

- Migrate test suite from PHPUnit to Testo. Internal change, no public API impact.

## 1.0.1 — 2026-06-03

- Mutation testing coverage raised to 100% MSI.

## 1.0.0 — 2026-06-01

- Initial release: reCAPTCHA v2 and v3 widgets, validators, PSR-18 client, Russian translations.

