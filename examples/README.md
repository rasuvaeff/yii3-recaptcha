# Examples

Runnable, self-contained scripts demonstrating yii3-recaptcha usage.

| Script | Shows | Needs server? |
|--------|-------|:-------------:|
| [`widget-v2.php`](widget-v2.php) | Rendering reCAPTCHA v2 widget | no |
| [`widget-v3.php`](widget-v3.php) | Rendering reCAPTCHA v3 widget | no |
| [`verify-headless.php`](verify-headless.php) | Server-side verification (SPA/API, stubbed HTTP) | no |

## Running

```bash
docker run --rm -v "$PWD/..":/app -w /app composer:2 php examples/widget-v2.php
docker run --rm -v "$PWD/..":/app -w /app composer:2 php examples/widget-v3.php
docker run --rm -v "$PWD/..":/app -w /app composer:2 php examples/verify-headless.php
```
