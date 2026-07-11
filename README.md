# rasuvaeff/yii3-recaptcha

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-recaptcha?label=stable&sort_semver=1)](https://packagist.org/packages/rasuvaeff/yii3-recaptcha)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-recaptcha)](https://packagist.org/packages/rasuvaeff/yii3-recaptcha)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-recaptcha/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-recaptcha/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-recaptcha/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-recaptcha/actions)
[![Psalm level](https://img.shields.io/badge/psalm-level%201-141F48?logo=psalm&logoColor=white)](https://github.com/rasuvaeff/yii3-recaptcha/blob/master/psalm.xml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-recaptcha/php)](https://packagist.org/packages/rasuvaeff/yii3-recaptcha)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-recaptcha)](LICENSE.md)

Google reCAPTCHA v2 and v3 widgets, `yiisoft/form-model` fields, and a
server-side validator for Yii3.

Provides `RecaptchaV2` / `RecaptchaV3` widgets and `RecaptchaV2Field` /
`RecaptchaV3Field` form-model fields for rendering challenges, plus
`RecaptchaV2Rule` / `RecaptchaV3Rule` with their handlers for server-side
verification through the Yii validator pipeline. In a **headless setup**
(SPA/Next.js frontend + API backend) you skip the rendering half entirely and
use only the validator — see [Headless / API-only](#headless--api-only). HTTP
calls go through any PSR-18 client.

> **Using an AI coding assistant?** [llms.txt](llms.txt) contains a compact
> API reference you can share with the model. Contributors: see [AGENTS.md](AGENTS.md).

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | `^8.3` |
| A PSR-18 HTTP client + PSR-17 factories | any implementation |
| `yiisoft/widget` | `^2.2` |
| `yiisoft/html` | `^4.0` |
| `yiisoft/validator` | `^2.5` |
| `yiisoft/translator` | `^3.0` |
| `yiisoft/request-provider` | `^1.3` |
| `yiisoft/form` + `yiisoft/form-model` | `^1.0` / `^1.1` (form fields) |

## Installation

```bash
composer require rasuvaeff/yii3-recaptcha
```

You also need a PSR-18 client and PSR-17 factories if your project doesn't
already ship one:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
# or another PSR-18 client plus PSR-17 factories
```

### DI configuration

The package ships `config/di.php` via `config-plugin`. Rule handlers are
constructed by the validator's **container-backed handler resolver** (the Yii3
default via `yiisoft/config`), which autowires the client, the client-IP
resolver and the optional translator — **no extra DI config required**.

> **Requires a DI-backed rule-handler resolver.** The handlers no longer carry a
> static fallback, so validating with a hand-built `new Validator(new
> SimpleRuleHandlerContainer())` will not resolve their dependencies. Use the
> container-backed resolver (the framework default). Set your keys in params
> (see [Dependency injection](#dependency-injection-yii3)).

## Headless / API-only

For a **SPA/Next.js frontend + Yii3 API backend** the widgets and fields are not
used — the frontend renders reCAPTCHA (e.g. `react-google-recaptcha`) and calls
`grecaptcha.execute(siteKey, { action })`, then sends the token to the API. The
backend only **verifies** it. Put the token on a request DTO property and attach
the rule:

```php
final class LoginRequest
{
    #[RecaptchaV3Rule(action: 'login', threshold: 0.5)]
    public string $recaptchaToken = '';
}
```

Map the incoming token (JSON body field or a header such as `X-Recaptcha-Token`)
to that property, then run your normal validation. Different endpoints use
different `action` names and thresholds. To make a graduated (allow / challenge /
deny) decision on the raw score instead of a pass/fail rule, verify imperatively:

```php
$result = $client->verifyV3($token);        // VerificationResult
if ($result->isTransportError()) { /* siteverify down — decide policy */ }
$score = $result->score;                     // apply your own score bands
```

Behind a proxy/CDN, bind a proxy-aware client-IP resolver — see
[Client IP behind a proxy](#client-ip-behind-a-proxy).

## Usage

### reCAPTCHA v2

```php
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Theme;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Size;

// siteKey comes from DI config (RecaptchaConfig.siteKeyV2)
echo RecaptchaV2::widget()
    ->withTheme(RecaptchaV2Theme::Dark)
    ->withSize(RecaptchaV2Size::Normal);
```

```php
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Rule;

class LoginForm
{
    #[RecaptchaV2Rule]
    public string $gRecaptchaResponse = '';
}
```

> **Field name mapping with Yii3 FormModel**
>
> Google's reCAPTCHA v2 widget always submits the response token as
> `g-recaptcha-response` (with hyphens). PHP does **not** normalize hyphens in POST
> keys, so `FormModel` will never receive the token if it expects `gRecaptchaResponse`
> directly.
>
> Use `withResponseFieldName()` to bind the token to your model property automatically
> — the widget renders a hidden input and the required JS copy callback:
>
> ```php
> <?= RecaptchaV2::widget()->withResponseFieldName('gRecaptchaResponse') ?>
> ```
>
> ```php
> #[RecaptchaV2Rule]
> public string $gRecaptchaResponse = '';
> ```

### reCAPTCHA v3

```php
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3;

// siteKey comes from DI config (RecaptchaConfig.siteKeyV3)
echo RecaptchaV3::widget();
```

The v3 widget renders the API `<script>`, a hidden input for the token, and a
script that fills the token on page load (or, with `withFormId()`, intercepts the
form submit, runs `grecaptcha.execute()` with the configured `action`, writes the
token into the hidden input, then submits — "invisible submit"):

```php
echo RecaptchaV3::widget()
    ->withAction('login')
    ->withFieldName('recaptchaToken')   // hidden input name bound to the model attribute
    ->withFormId('login-form')          // optional: enable invisible-submit binding
    ->withBadge(RecaptchaV3Badge::Hidden); // optional: hide badge + render required legal notice
```

When the badge is hidden you must keep the reCAPTCHA legal notice visible — the
widget renders it for you. All values are JSON-encoded with XSS-safe flags before
being embedded in the inline script.

```php
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Rule;

class LoginForm
{
    #[RecaptchaV3Rule(threshold: 0.5, action: 'login')]
    public string $recaptchaToken = '';
}
```

### Form fields (form-model)

For server-rendered forms, `RecaptchaV2Field` / `RecaptchaV3Field` integrate with
`yiisoft/form-model`: they bind the token to a model property and delegate
rendering to the widgets (one rendering path, so widget and field stay in sync).

```php
use Rasuvaeff\Yii3Recaptcha\Field\RecaptchaV3Field;

echo RecaptchaV3Field::field($formModel, 'recaptchaToken')
    ->siteKey($siteKeyV3)
    ->action('login')
    ->formId('login-form');   // optional invisible-submit binding
```

```php
use Rasuvaeff\Yii3Recaptcha\Field\RecaptchaV2Field;

echo RecaptchaV2Field::field($formModel, 'recaptchaToken')
    ->siteKey($siteKeyV2)
    ->theme(RecaptchaV2Theme::Dark);
```

### Content-Security-Policy

Both widgets emit inline `<script>` (and `<style>`). Under a strict CSP without
`unsafe-inline`, pass a nonce — it is applied to every emitted tag:

```php
echo RecaptchaV3::widget()->withNonce($cspNonce);
echo RecaptchaV3Field::field($form, 'recaptchaToken')->siteKey($key)->nonce($cspNonce);
```

### Client IP behind a proxy

When `sendRemoteIp` is enabled the client IP is resolved through
`ClientIpResolverInterface`. The default `RemoteAddrClientIpResolver` reads
`REMOTE_ADDR` (validated with `FILTER_VALIDATE_IP`) — correct only when you are
not behind a proxy/CDN, or when a trusted-hosts middleware has already normalised
`REMOTE_ADDR`. Behind an un-normalised proxy, bind your own resolver:

```php
// config/common/di.php
ClientIpResolverInterface::class => static fn (MyIpDetector $d): ClientIpResolverInterface
    => new class ($d) implements ClientIpResolverInterface {
        public function __construct(private MyIpDetector $d) {}
        public function resolve(): ?string { return $this->d->detect(); }
    },
```

### Retrying transient failures

`RecaptchaClient` takes any PSR-18 client, so you can wrap it with a retrying
decorator (e.g. [`rasuvaeff/retry`](https://github.com/rasuvaeff/retry)'s
`Http\RetryingHttpClient`) to survive transient network errors — retry only on
transport exceptions, since `siteverify` is a `POST`. No package change needed;
inject the decorated client where `RecaptchaClient` gets its PSR-18 client.

### Dependency injection (Yii3)

Override params in your application config:

```php
// config/params.php
return [
    'rasuvaeff/yii3-recaptcha' => [
        'siteKeyV2' => $_ENV['RECAPTCHA_SITE_KEY_V2'],
        'secretV2' => $_ENV['RECAPTCHA_SECRET_V2'],
        'siteKeyV3' => $_ENV['RECAPTCHA_SITE_KEY_V3'],
        'secretV3' => $_ENV['RECAPTCHA_SECRET_V3'],
        'sendRemoteIp' => true,
        'translation.category' => 'yii3-recaptcha',
    ],
];
```

### Translations

| Locale | File |
|--------|------|
| `ru` | `messages/ru/yii3-recaptcha.php` |

To add more languages, create `messages/<locale>/yii3-recaptcha.php`:

```php
<?php

declare(strict_types=1);

return [
    'The CAPTCHA verification failed.' => 'Your translated message.',
    'The CAPTCHA score is too low.' => 'Your translated message.',
    'The CAPTCHA action does not match.' => 'Your translated message.',
];
```

## Components

### `RecaptchaV2` (widget)

| Method | Description |
|--------|-------------|
| `withSiteKey(string $siteKey): self` | Google site key (required). |
| `withId(string $id): self` | DOM id for the widget container. Default: auto-generated unique id (supports multiple widgets per page). |
| `withTheme(RecaptchaV2Theme $theme): self` | `Light` or `Dark`. Default: `Light`. |
| `withType(RecaptchaV2Type $type): self` | `Image` or `Audio`. Default: `Image`. |
| `withSize(RecaptchaV2Size $size): self` | `Normal`, `Compact`, or `Invisible`. Default: `Normal`. |
| `withJsApiUrl(string $url): self` | Override the script URL. |
| `withCallback(string $cb): self` | JS callback on success. |
| `withExpiredCallback(string $cb): self` | JS callback on expiry. |
| `withErrorCallback(string $cb): self` | JS callback on error. |
| `withNonce(string $nonce): self` | CSP `nonce` on every emitted `<script>`. |
| `render(): string` | Returns HTML. Throws `MissingSiteKeyException` if `siteKey` is not set. |

### `RecaptchaV3` (widget)

| Method | Description |
|--------|-------------|
| `withSiteKey(string $siteKey): self` | Google site key (required). |
| `withAction(string $action): self` | Action name passed to `grecaptcha.execute()`. Default: `submit`. |
| `withFieldName(string $name): self` | Hidden input name (model attribute). Default: `g-recaptcha-response`. |
| `withFieldId(string $id): self` | Hidden input DOM id. Default: auto-generated unique id. |
| `withFormId(string $id): self` | Enable invisible-submit binding to this form id. Default: none (token filled on load). |
| `withBadge(RecaptchaV3Badge $badge): self` | Badge position: `BottomRight` (default), `BottomLeft`, or `Hidden` (+ legal notice). |
| `withJsApiUrl(string $url): self` | Override the script URL. |
| `withNonce(string $nonce): self` | CSP `nonce` on every emitted `<script>`/`<style>`. |
| `render(): string` | Returns HTML (script + hidden input + inline script). Throws `MissingSiteKeyException` if `siteKey` is not set. |

### `RecaptchaConfig`

```php
final readonly class RecaptchaConfig
{
    public function __construct(
        public string $siteKeyV2 = '',
        public string $secretV2 = '',
        public string $siteKeyV3 = '',
        public string $secretV3 = '',
        public string $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify',
        public bool $sendRemoteIp = false,
    ) {}
}
```

### `RecaptchaClient`

```php
final readonly class RecaptchaClient
{
    public function verify(string $token, ?string $clientIp = null): VerificationResult;     // secretV2
    public function verifyV3(string $token, ?string $clientIp = null): VerificationResult;   // secretV3
    public function verifyWithSecret(string $token, string $secret, ?string $clientIp = null): VerificationResult;
}
```

`verify()` uses `secretV2`, `verifyV3()` uses `secretV3` from config.
`verifyWithSecret()` uses a custom secret (for v2/v3 rules that override it).
The client **never throws**: on a transport/HTTP/JSON failure it returns a failed
`VerificationResult` tagged `TRANSPORT_ERROR` (fail-closed). In the validator
pipeline the handlers resolve `clientIp` through `ClientIpResolverInterface` — only
when the rule's `sendRemoteIp` and `RecaptchaConfig::sendRemoteIp` are both enabled.

### `VerificationResult`

```php
final readonly class VerificationResult
{
    public bool $success;
    public array $errorCodes;   // string[]
    public ?float $score;       // v3 only
    public ?string $action;     // v3 only
    public ?string $hostname;
    public ?string $challengeTs;

    public const string TRANSPORT_ERROR = 'transport-error';
    public static function transportError(): self;  // failed result for an unreachable endpoint
    public function isTransportError(): bool;        // true on transport/HTTP/JSON failure (vs a real verdict)
}
```

### Fields, resolver, exceptions

| Type | Purpose |
|------|---------|
| `Field\RecaptchaV2Field`, `Field\RecaptchaV3Field` | `yiisoft/form-model` fields (`::field($model, $property)`), delegate to the widgets. |
| `ClientIpResolverInterface` + `RemoteAddrClientIpResolver` | Pluggable client-IP resolution; default reads validated `REMOTE_ADDR`. |
| `Exception\RecaptchaException` | Marker interface for all package exceptions. |
| `Exception\MissingSiteKeyException` | Thrown when a widget/field renders without a site key. |

### `RecaptchaV2Rule` / `RecaptchaV2RuleHandler`

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `message` | `string` | `'The CAPTCHA verification failed.'` | Error message. |
| `secret` | `?string` | `null` | Override secret. |
| `sendRemoteIp` | `bool` | `false` | Forward client IP. |
| `failOpenOnError` | `bool` | `false` | Pass validation when siteverify is unreachable (transport error). Default fails closed. |
| `skipOnEmpty` | `bool\|callable\|null` | `null` | Skip on empty. |
| `skipOnError` | `bool` | `false` | Skip on prior error. |
| `when` | `?Closure` | `null` | Conditional execution. |

### `RecaptchaV3Rule` / `RecaptchaV3RuleHandler`

Same as v2, plus:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `threshold` | `float` | `0.5` | Minimum score in the `0.0..1.0` range. |
| `action` | `?string` | `null` | Expected action name. |
| `scoreTooLowMessage` | `string` | `'The CAPTCHA score is too low.'` | Score error. |
| `actionMismatchMessage` | `string` | `'The CAPTCHA action does not match.'` | Action error. |

### Enums

| Enum | Values |
|------|--------|
| `RecaptchaV2Theme` | `Light`, `Dark` |
| `RecaptchaV2Type` | `Image`, `Audio` |
| `RecaptchaV2Size` | `Normal`, `Compact`, `Invisible` |

## Security

- The widget renders **public** site keys in HTML — this is intentional.
- Secrets are server-side only.
- Token verification goes over HTTPS.
- v3 score threshold and action validation prevent token reuse across contexts.
- Widget JS is built with `json_encode` using `JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP`,
  so callback names, actions, ids and other values cannot break out of the
  inline `<script>` (no raw string concatenation).
- `sendRemoteIp` is opt-in; the client IP comes from `ClientIpResolverInterface`.
  The default reads `REMOTE_ADDR` (validated with `FILTER_VALIDATE_IP`), not user
  input. Behind a proxy/CDN bind a proxy-aware resolver so the real client IP is
  used (see [Client IP behind a proxy](#client-ip-behind-a-proxy)).
- **Fail-closed by default.** If the siteverify endpoint is unreachable the client
  returns a failed result (`TRANSPORT_ERROR`) and the rule rejects — an outage
  cannot silently let bots through. Opt into fail-open per rule with
  `failOpenOnError: true` when availability matters more than strictness.
- Widgets accept a CSP `nonce` via `withNonce()` for strict Content-Security-Policy.

## Examples

See [examples/](examples/) for runnable scripts.

| Script | Shows | Needs server? |
|--------|-------|:-------------:|
| [`widget-v2.php`](examples/widget-v2.php) | Rendering v2 widget | no |
| [`widget-v3.php`](examples/widget-v3.php) | Rendering v3 widget | no |

## Development

No PHP/Composer on the host — run in Docker via the `composer:2` image:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
```

`make test-coverage` and `make mutation` bootstrap `pcov` inside the Docker container
because the base `composer:2` image does not ship with a coverage driver.

Or with Make:

```bash
make install
make build
make cs-fix
make test
```

CI runs `composer build` on PHP 8.3, 8.4, and 8.5.

## License

[BSD-3-Clause](LICENSE.md)
