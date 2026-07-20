# rasuvaeff/yii3-recaptcha

[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-recaptcha?label=stable&sort_semver=1)](https://packagist.org/packages/rasuvaeff/yii3-recaptcha)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-recaptcha)](https://packagist.org/packages/rasuvaeff/yii3-recaptcha)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-recaptcha/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-recaptcha/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-recaptcha/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-recaptcha/actions)
[![Psalm level](https://img.shields.io/badge/psalm-level%201-141F48?logo=psalm&logoColor=white)](https://github.com/rasuvaeff/yii3-recaptcha/blob/master/psalm.xml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-recaptcha/php)](https://packagist.org/packages/rasuvaeff/yii3-recaptcha)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-recaptcha)](LICENSE.md)
[English version](README.md)

Виджеты Google reCAPTCHA v2 и v3, поля `yiisoft/form-model` и серверный
валидатор для Yii3.

Предоставляет виджеты `RecaptchaV2` / `RecaptchaV3` и form-model-поля
`RecaptchaV2Field` / `RecaptchaV3Field` для рендеринга challenge'ей, а также
`RecaptchaV2Rule` / `RecaptchaV3Rule` с их обработчиками для серверной
верификации через валидаторный пайплайн Yii. В **headless-сетапе**
(SPA/Next.js-фронтенд + API-бэкенд) вы полностью пропускаете половину рендера и
используете только валидатор — см. [Headless / только API](#headless--только-api).
HTTP-вызовы идут через любой PSR-18-клиент.

> **Используете AI-ассистента?** [llms.txt](llms.txt) содержит компактный
> API-справочник, которым можно поделиться с моделью. Контрибьюторам: см. [AGENTS.md](AGENTS.md).

## Требования

| Требование | Версия |
|-------------|---------|
| PHP | `^8.3` |
| PSR-18 HTTP-клиент + PSR-17 фабрики | любая реализация |
| `yiisoft/widget` | `^2.2` |
| `yiisoft/html` | `^4.0` |
| `yiisoft/validator` | `^2.5` |
| `yiisoft/translator` | `^3.0` |
| `yiisoft/request-provider` | `^1.3` |
| `yiisoft/form` + `yiisoft/form-model` | `^1.0` / `^1.1` (поля формы) |

## Установка

```bash
composer require rasuvaeff/yii3-recaptcha
```

Если в проекте ещё нет PSR-18-клиента и PSR-17-фабрик, добавьте их:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
# или любой другой PSR-18-клиент плюс PSR-17-фабрики
```

### Конфигурация DI

Пакет несёт `config/di.php` через `config-plugin`. Обработчики правил строятся
**container-backed handler-resolver'ом** валидатора (дефолт Yii3 через
`yiisoft/config`), который autowire'ит клиент, IP-резолвер клиента и опциональный
переводчик — **дополнительная DI-конфигурация не нужна**.

> **Работает из коробки; DI-инжектится при наличии.** Обработчики правил берут
> свои зависимости (клиент, IP-резолвер, переводчик) как опциональные
> конструкторные аргументы и падают на `RecaptchaRegistry`, который
> **bootstrap** `config-plugin` пакета наполняет из контейнера. Поэтому они
> работают с дефолтным `SimpleRuleHandlerContainer` `yiisoft/validator`
> (no-arg `new`) без всякой доп. конфигурации. Когда container-backed-резолвер
> их строит, побеждают инжекченные deps, а к реестру не обращаются.
>
> Приложение обязано предоставить **PSR-18 `ClientInterface`** и PSR-17-фабрики
> (из них строится `RecaptchaClient`) и задать ключи в параметрах (см.
> [Внедрение зависимостей](#внедрение-зависимостей-yii3)). Два опциональных
> чисто-DI-сетапа, если вы не хотите полагаться на статический фолбэк:
>
> ```php
> // A) container-backed resolver (все обработчики правил резолвятся через контейнер)
> RuleHandlerResolverInterface::class => RuleHandlerContainer::class,
>
> // B) сохранить дефолтный резолвер, предварительно зарегистрировав DI-built обработчики как инстансы
> RuleHandlerResolverInterface::class => static fn (
>     RecaptchaV2RuleHandler $v2, RecaptchaV3RuleHandler $v3,
> ): SimpleRuleHandlerContainer => new SimpleRuleHandlerContainer([
>     RecaptchaV2RuleHandler::class => $v2,
>     RecaptchaV3RuleHandler::class => $v3,
> ]),
> ```

## Headless / только API

Для **SPA/Next.js-фронтенда + Yii3 API-бэкенда** виджеты и поля не используются
— фронтенд рендерит reCAPTCHA (например, `react-google-recaptcha`) и вызывает
`grecaptcha.execute(siteKey, { action })`, затем отправляет токен в API. Бэкенд
только **верифицирует** его. Положите токен в свойство DTO запроса и навесьте
правило:

```php
final class LoginRequest
{
    #[RecaptchaV3Rule(action: 'login', threshold: 0.5)]
    public string $recaptchaToken = '';
}
```

Отмапьте входящий токен (поле JSON-тела или заголовок вроде `X-Recaptcha-Token`)
на это свойство, затем запускайте обычную валидацию. Разные эндпоинты используют
разные `action` и пороги. Чтобы принимать градуированное решение (allow /
challenge / deny) по сырому score вместо pass/fail-правила, верифицируйте
императивно:

```php
$result = $client->verifyV3($token);        // VerificationResult
if ($result->isTransportError()) { /* siteverify down — decide policy */ }
$score = $result->score;                     // apply your own score bands
```

За прокси/CDN привяжите proxy-aware-резолвер клиентского IP — см.
[Client IP за прокси](#client-ip-за-прокси).

## Использование

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

> **Маппинг имени поля с Yii3 FormModel**
>
> Виджет Google reCAPTCHA v2 всегда сабмитит response-токен как
> `g-recaptcha-response` (с дефисами). PHP **не** нормализует дефисы в POST-ключах,
> поэтому `FormModel` никогда не получит токен, если ожидает `gRecaptchaResponse`
> напрямую.
>
> Используйте `withResponseFieldName()`, чтобы автоматически привязать токен к
> свойству модели — виджет рендерит скрытый input и нужный JS callback копирования:
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

v3-виджет рендерит API `<script>`, скрытый input для токена и скрипт, заполняющий
токен при загрузке страницы (либо, при `withFormId()`, перехватывающий сабмит
формы, вызывающий `grecaptcha.execute()` со сконфигурированным `action`,
записывающий токен в скрытый input и затем сабмитящий — «invisible submit»):

```php
echo RecaptchaV3::widget()
    ->withAction('login')
    ->withFieldName('recaptchaToken')   // hidden input name bound to the model attribute
    ->withFormId('login-form')          // optional: enable invisible-submit binding
    ->withBadge(RecaptchaV3Badge::Hidden); // optional: hide badge + render required legal notice
```

Когда бейдж спрятан, вы обязаны держать юридическое уведомление reCAPTCHA видимым
— виджет рендерит его за вас. Все значения JSON-кодируются с XSS-безопасными
флагами перед встраиванием во inline-скрипт.

```php
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Rule;

class LoginForm
{
    #[RecaptchaV3Rule(threshold: 0.5, action: 'login')]
    public string $recaptchaToken = '';
}
```

### Поля формы (form-model)

Для серверного рендера форм `RecaptchaV2Field` / `RecaptchaV3Field` интегрируются
с `yiisoft/form-model`: они привязывают токен к свойству модели и делегируют
рендер виджетам (один путь рендера, поэтому виджет и поле остаются синхронными).

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

Оба виджета эммитят inline `<script>` (и `<style>`). Под строгим CSP без
`unsafe-inline` передайте nonce — он применяется к каждому эммитимому тегу:

```php
echo RecaptchaV3::widget()->withNonce($cspNonce);
echo RecaptchaV3Field::field($form, 'recaptchaToken')->siteKey($key)->nonce($cspNonce);
```

### Client IP за прокси

Когда включён `sendRemoteIp`, IP клиента резолвится через
`ClientIpResolverInterface`. Дефолтный `RemoteAddrClientIpResolver` читает
`REMOTE_ADDR` (с валидацией `FILTER_VALIDATE_IP`) — корректно, только если вы не
за прокси/CDN, либо когда trusted-hosts-middleware уже нормализовал `REMOTE_ADDR`.
За ненормализованным прокси привяжите собственный резолвер:

```php
// config/common/di.php
ClientIpResolverInterface::class => static fn (MyIpDetector $d): ClientIpResolverInterface
    => new class ($d) implements ClientIpResolverInterface {
        public function __construct(private MyIpDetector $d) {}
        public function resolve(): ?string { return $this->d->detect(); }
    },
```

### Повторы транзиентных сбоев

`RecaptchaClient` принимает любой PSR-18-клиент, поэтому вы можете обернуть его
декоратором с повторами (например, `Http\RetryingHttpClient` из
[`rasuvaeff/retry`](https://github.com/rasuvaeff/retry)), чтобы переживать
транзиентные сетевые ошибки — повторяйте только на transport-исключениях,
поскольку `siteverify` — это `POST`. Никаких изменений в пакете; внедрите
декорированный клиент туда, где `RecaptchaClient` получает свой PSR-18-клиент.

### Внедрение зависимостей (Yii3)

Перекройте параметры в конфигурации приложения:

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

### Переводы

| Локаль | Файл |
|--------|------|
| `ru` | `messages/ru/yii3-recaptcha.php` |

Чтобы добавить языки, создайте `messages/<locale>/yii3-recaptcha.php`:

```php
<?php

declare(strict_types=1);

return [
    'The CAPTCHA verification failed.' => 'Your translated message.',
    'The CAPTCHA score is too low.' => 'Your translated message.',
    'The CAPTCHA action does not match.' => 'Your translated message.',
];
```

## Компоненты

### `RecaptchaV2` (виджет)

| Метод | Описание |
|--------|-------------|
| `withSiteKey(string $siteKey): self` | Google site key (обязателен). |
| `withId(string $id): self` | DOM-id для контейнера виджета. По умолчанию: автогенерируемый уникальный id (поддерживает несколько виджетов на странице). |
| `withTheme(RecaptchaV2Theme $theme): self` | `Light` или `Dark`. По умолчанию: `Light`. |
| `withType(RecaptchaV2Type $type): self` | `Image` или `Audio`. По умолчанию: `Image`. |
| `withSize(RecaptchaV2Size $size): self` | `Normal`, `Compact` или `Invisible`. По умолчанию: `Normal`. |
| `withJsApiUrl(string $url): self` | Переопределить URL скрипта. |
| `withCallback(string $cb): self` | JS-callback при успехе. |
| `withExpiredCallback(string $cb): self` | JS-callback при истечении. |
| `withErrorCallback(string $cb): self` | JS-callback при ошибке. |
| `withNonce(string $nonce): self` | CSP `nonce` на каждый эммитимый `<script>`. |
| `render(): string` | Возвращает HTML. Бросает `MissingSiteKeyException`, если `siteKey` не задан. |

### `RecaptchaV3` (виджет)

| Метод | Описание |
|--------|-------------|
| `withSiteKey(string $siteKey): self` | Google site key (обязателен). |
| `withAction(string $action): self` | Имя action, передаваемое в `grecaptcha.execute()`. По умолчанию: `submit`. |
| `withFieldName(string $name): self` | Имя скрытого input (атрибут модели). По умолчанию: `g-recaptcha-response`. |
| `withFieldId(string $id): self` | DOM-id скрытого input. По умолчанию: автогенерируемый уникальный id. |
| `withFormId(string $id): self` | Включить invisible-submit-биндинг к этому id формы. По умолчанию: нет (токен заполняется при загрузке). |
| `withBadge(RecaptchaV3Badge $badge): self` | Позиция бейджа: `BottomRight` (по умолчанию), `BottomLeft` или `Hidden` (+ юридическое уведомление). |
| `withJsApiUrl(string $url): self` | Переопределить URL скрипта. |
| `withNonce(string $nonce): self` | CSP `nonce` на каждый эммитимый `<script>`/`<style>`. |
| `render(): string` | Возвращает HTML (script + скрытый input + inline script). Бросает `MissingSiteKeyException`, если `siteKey` не задан. |

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

`verify()` использует `secretV2`, `verifyV3()` использует `secretV3` из конфига.
`verifyWithSecret()` использует кастомный секрет (для правил v2/v3, которые его
переопределяют). Клиент **никогда не бросает исключения**: при transport/HTTP/JSON-сбое
он возвращает провальный `VerificationResult` с тегом `TRANSPORT_ERROR`
(fail-closed). В валидаторном пайплайне обработчики резолвят `clientIp` через
`ClientIpResolverInterface` — только когда одновременно включены `sendRemoteIp`
правила и `RecaptchaConfig::sendRemoteIp`.

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

### Поля, резолвер, исключения

| Тип | Назначение |
|------|---------|
| `Field\RecaptchaV2Field`, `Field\RecaptchaV3Field` | Поля `yiisoft/form-model` (`::field($model, $property)`), делегируют виджетам. |
| `ClientIpResolverInterface` + `RemoteAddrClientIpResolver` | Подключаемое разрешение клиентского IP; дефолт читает валидированный `REMOTE_ADDR`. |
| `RecaptchaRegistry` | Статический фолбэк (`configure(client, ipResolver?, translator?)`) для no-arg-конструирования обработчиков; наполняется bootstrap'ом. |
| `Exception\RecaptchaException` | Marker-интерфейс всех исключений пакета. |
| `Exception\MissingSiteKeyException` | Бросается, когда виджет/поле рендерится без site key. |
| `Exception\MissingClientException` | Бросается, когда у обработчика нет клиента (ни инжекченного, ни зарегистрированного). |

### `RecaptchaV2Rule` / `RecaptchaV2RuleHandler`

| Параметр | Тип | По умолчанию | Описание |
|-----------|------|---------|-------------|
| `message` | `string` | `'The CAPTCHA verification failed.'` | Сообщение об ошибке. |
| `secret` | `?string` | `null` | Переопределить секрет. |
| `sendRemoteIp` | `bool` | `false` | Пересылать клиентский IP. |
| `failOpenOnError` | `bool` | `false` | Пропускать валидацию, когда siteverify недоступен (transport error). По умолчанию fail-closed. |
| `skipOnEmpty` | `bool\|callable\|null` | `null` | Скипать при пустом. |
| `skipOnError` | `bool` | `false` | Скипать при предшествующей ошибке. |
| `when` | `?Closure` | `null` | Условное выполнение. |

### `RecaptchaV3Rule` / `RecaptchaV3RuleHandler`

Как в v2, плюс:

| Параметр | Тип | По умолчанию | Описание |
|-----------|------|---------|-------------|
| `threshold` | `float` | `0.5` | Минимальный score в диапазоне `0.0..1.0`. |
| `action` | `?string` | `null` | Ожидаемое имя action. |
| `scoreTooLowMessage` | `string` | `'The CAPTCHA score is too low.'` | Ошибка по score. |
| `actionMismatchMessage` | `string` | `'The CAPTCHA action does not match.'` | Ошибка по action. |

### Enum'ы

| Enum | Значения |
|------|--------|
| `RecaptchaV2Theme` | `Light`, `Dark` |
| `RecaptchaV2Type` | `Image`, `Audio` |
| `RecaptchaV2Size` | `Normal`, `Compact`, `Invisible` |

## Безопасность

- Виджет рендерит **публичные** site keys в HTML — это намеренно.
- Секреты только серверные.
- Верификация токена идёт по HTTPS.
- Порог score и валидация action в v3 предотвращают повторное использование токена в разных контекстах.
- JS виджета строится через `json_encode` с флагами
  `JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP`, поэтому имена callback'ов,
  action, id и прочие значения не могут вырваться из inline `<script>` (никакой
  конкатенации сырых строк).
- `sendRemoteIp` опционален; клиентский IP берётся из `ClientIpResolverInterface`.
  По умолчанию читает `REMOTE_ADDR` (с валидацией `FILTER_VALIDATE_IP`), а не из
  пользовательского ввода. За прокси/CDN привяжите proxy-aware-резолвер, чтобы
  использовался реальный клиентский IP (см. [Client IP за прокси](#client-ip-за-прокси)).
- **Fail-closed по умолчанию.** Если endpoint siteverify недоступен, клиент
  возвращает провальный результат (`TRANSPORT_ERROR`), и правило отвергает —
  сбой не может молча пропустить ботов. Включайте fail-open на отдельное правило
  через `failOpenOnError: true`, когда доступность важнее строгости.
- Виджеты принимают CSP `nonce` через `withNonce()` для строгого Content-Security-Policy.

## Примеры

См. [examples/](examples/) — исполняемые скрипты.

| Скрипт | Что показывает | Нужен сервер? |
|--------|-------|:-------------:|
| [`widget-v2.php`](examples/widget-v2.php) | Рендеринг v2-виджета | нет |
| [`widget-v3.php`](examples/widget-v3.php) | Рендеринг v3-виджета | нет |

## Разработка

На хосте нет PHP/Composer — запускайте в Docker через образ `composer:2`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
```

`make test-coverage` и `make mutation` поднимают `pcov` внутри Docker-контейнера,
потому что в базовом образе `composer:2` нет драйвера покрытия.

Или через Make:

```bash
make install
make build
make cs-fix
make test
```

CI запускает `composer build` на PHP 8.3, 8.4 и 8.5.

## Лицензия

[BSD-3-Clause](LICENSE.md)
