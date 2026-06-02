# rasuvaeff/yii3-recaptcha

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-recaptcha?label=stable&sort_semver=1)](https://packagist.org/packages/rasuvaeff/yii3-recaptcha)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-recaptcha)](https://packagist.org/packages/rasuvaeff/yii3-recaptcha)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-recaptcha/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-recaptcha/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-recaptcha/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-recaptcha/actions)
[![Coverage](https://codecov.io/gh/rasuvaeff/yii3-recaptcha/branch/master/graph/badge.svg)](https://codecov.io/gh/rasuvaeff/yii3-recaptcha)
[![Psalm level](https://img.shields.io/badge/psalm-level%201-141F48?logo=psalm&logoColor=white)](https://github.com/rasuvaeff/yii3-recaptcha/blob/master/psalm.xml)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-recaptcha)](LICENSE.md)

Google reCAPTCHA v2 and v3 widget and server-side validator for Yii3.

Provides `RecaptchaV2` / `RecaptchaV3` widgets for rendering challenges in forms
and `RecaptchaV2Rule` / `RecaptchaV3Rule` with their handlers for server-side
verification through the Yii validator pipeline. HTTP calls go through any PSR-18 client.

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

Since v1.0.5 the package ships `config/bootstrap.php` via `config-plugin`. On every
application boot it populates `RecaptchaRegistry` with the handler dependencies, so
`RecaptchaV2RuleHandler` / `RecaptchaV3RuleHandler` work even with the default
`SimpleRuleHandlerContainer` — **no extra DI config required**.

If your app already uses `RuleHandlerContainer` for other reasons, keep it; this
package is compatible with both resolvers.

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
| `render(): string` | Returns HTML. Throws if `siteKey` is not set. |

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
| `render(): string` | Returns HTML (script + hidden input + inline script). Throws if `siteKey` is not set. |

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
In the validator pipeline the handlers resolve `clientIp` from the current request
via `yiisoft/request-provider` (`RequestProviderInterface`, `REMOTE_ADDR`) — only
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
}
```

### `RecaptchaV2Rule` / `RecaptchaV2RuleHandler`

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `message` | `string` | `'The CAPTCHA verification failed.'` | Error message. |
| `secret` | `?string` | `null` | Override secret. |
| `sendRemoteIp` | `bool` | `false` | Forward client IP. |
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
- `sendRemoteIp` is opt-in; the client IP comes from the current request via
  `RequestProviderInterface` (`REMOTE_ADDR`), not from user input.

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
