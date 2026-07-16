# rasuvaeff/yii3-recaptcha
[![Stable Version](https://img.shields.io/packagist/v/rasuvaeff/yii3-recaptcha?label=stable&sort_semver=1)](https://packagist.org/packages/rasuvaeff/yii3-recaptcha)
[![Total Downloads](https://img.shields.io/packagist/dt/rasuvaeff/yii3-recaptcha)](https://packagist.org/packages/rasuvaeff/yii3-recaptcha)
[![Build](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-recaptcha/build.yml?branch=master)](https://github.com/rasuvaeff/yii3-recaptcha/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/rasuvaeff/yii3-recaptcha/static-analysis.yml?branch=master&label=static%20analysis)](https://github.com/rasuvaeff/yii3-recaptcha/actions)
[![Psalm level](https://img.shields.io/badge/psalm-level%201-141F48?logo=psalm&logoColor=white)](https://github.com/rasuvaeff/yii3-recaptcha/blob/master/psalm.xml)
[![PHP](https://img.shields.io/packagist/dependency-v/rasuvaeff/yii3-recaptcha/php)](https://packagist.org/packages/rasuvaeff/yii3-recaptcha)
[![License](https://img.shields.io/packagist/l/rasuvaeff/yii3-recaptcha)](LICENSE.md)
Виджеты Google reCAPTCHA v2 и v3, поля `yiisoft/form-model` и серверный валидатор
 для Yii3.

 Предоставляет виджеты `RecaptchaV2` / `RecaptchaV3` и `RecaptchaV2Field` /
 `RecaptchaV3Field` поля модели формы для задач отрисовки, а также
 `RecaptchaV2Rule` / `RecaptchaV3Rule` с их обработчиками для проверки
 на стороне сервера через валидатор Yii трубопровод. В **безголовой настройке**
 (интерфейс SPA/Next.js + серверная часть API) вы полностью пропускаете половину рендеринга и
 используете только валидатор — см. [Headless/только API](#headless--api-only). HTTP-вызовы
 проходят через любого клиента PSR-18.

 > **Используете помощника по кодированию с использованием искусственного интеллекта?** [llms.txt](llms.txt) содержит компактную ссылку
 > API, которой вы можете поделиться с моделью. Авторы: см. [AGENTS.md](AGENTS.md). @@ЛИНИЯ@@
## Требования
| Требование | Версия |
 |-------------|---------|
 | PHP | `^8.3` |
 | HTTP-клиент PSR-18 + фабрики PSR-17 | любая реализация |
 | `yiisoft/виджет` | `^2.2` |
 | `yiisoft/html` | `^4.0` |
 | `yiisoft/валидатор` | `^2,5` |
 | `yiisoft/переводчик` | `^3.0` |
 | `yiisoft/поставщик запросов` | `^1.3` |
 | `yiisoft/form` + `yiisoft/form-model` | `^1.0` / `^1.1` (поля формы) | @@ЛИНИЯ@@
## Установка
```bash
composer require rasuvaeff/yii3-recaptcha
```
Вам также понадобится клиент PSR-18 и фабрики PSR-17, если
 еще не поставляет ваш проект:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
# or another PSR-18 client plus PSR-17 factories
```
### Конфигурация цифрового входа
Пакет поставляется `config/di.php` через `config-plugin`. Обработчики правил — это
, созданные **распознавателем обработчиков, поддерживаемым контейнером** валидатора (по умолчанию Yii3
 через `yiisoft/config`), который автоматически подключает клиент, преобразователь клиент-IP
 и дополнительный транслятор — **дополнительная конфигурация DI не требуется**.

 > **Работает «из коробки»; DI-внедряется, когда доступно.** Обработчики правил принимают
 > свои зависимости (клиент, преобразователь IP, транслятор) в качестве необязательных аргументов конструктора
 > и возвращаются к `RecaptchaRegistry`, который
 > config-plugin **bootstrap** пакета заполняет из контейнера. Таким образом, они работают с
 > `yiisoft/validator` по умолчанию `SimpleRuleHandlerContainer` (без аргументов `new`) без каких-либо дополнительных настроек
 >. Когда преобразователь на основе контейнера создает их, введенный deps
 > выигрывает, и к реестру никогда не обращаются.
 >
 > Приложение должно предоставить фабрики **PSR-18 `ClientInterface`** и PSR-17
 > (на их основе создается `RecaptchaClient`) и установить ключи в параметрах (см.
 > [Внедрение зависимостей](#dependent-injection-yii3)). Две дополнительные настройки чистого DI
 >, если вы предпочитаете не полагаться на статический резерв:
 >
 > ```php
 > // A) преобразователь на основе контейнера (все обработчики правил разрешаются через контейнер)
 > RuleHandlerResolverInterface::class => RuleHandlerContainer::class,
 >
 > // B) сохраните преобразователь по умолчанию, предварительно зарегистрировать обработчики, созданные DI, как экземпляры
 > RuleHandlerResolverInterface::class => static fn (
 > RecaptchaV2RuleHandler $v2, RecaptchaV3RuleHandler $v3,
 > ): SimpleRuleHandlerContainer => new SimpleRuleHandlerContainer([
 > RecaptchaV2RuleHandler::class => $v2,
 > RecaptchaV3RuleHandler::class => $v3,
 > ]),
 > ```

## Безголовый / только API
Для **интерфейса SPA/Next.js + бэкэнда API Yii3** виджеты и поля
 не используются — интерфейс отображает reCAPTCHA (например, `react-google-recaptcha`) и вызывает
 `grecaptcha.execute(siteKey, { action })`, а затем отправляет токен в API. Серверная часть
 только **проверяет** это. Поместите токен в свойство DTO запроса и прикрепите
 к правилу:

```php
final class LoginRequest
{
    #[RecaptchaV3Rule(action: 'login', threshold: 0.5)]
    public string $recaptchaToken = '';
}
```
Сопоставьте входящий токен (поле тела JSON или заголовок, например `X-Recaptcha-Token`)
 с этим свойством, а затем запустите обычную проверку. Различные конечные точки используют
 разные имена и пороговые значения «действий». Чтобы принять постепенное решение (разрешить/оспорить/
 отклонить) по исходному результату вместо правила «прошел/не прошел», обязательно подтвердите:

```php
$result = $client->verifyV3($token);        // VerificationResult
if ($result->isTransportError()) { /* siteverify down — decide policy */ }
$score = $result->score;                     // apply your own score bands
```
За прокси/CDN привяжите преобразователь клиентских IP-адресов, поддерживающий прокси-сервер — см.
 [IP-адрес клиента за прокси-сервером](#client-ip-behind-a-proxy). @@ЛИНИЯ@@
## Использование
### реКАПЧА v2
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
> **Сопоставление имен полей с помощью Yii3 FormModel**
 >
 > Виджет Google reCAPTCHA v2 всегда отправляет токен ответа как
 > `g-recaptcha-response` (с дефисами). PHP **не** нормализует дефисы в ключах POST
 >, поэтому FormModel никогда не получит токен, если ожидает непосредственно `gRecaptchaResponse`
 >.
 >
 > Используйте `withResponseFieldName()` для автоматической привязки токена к свойству вашей модели
 > — виджет отображает скрытый ввод и необходимый обратный вызов копирования JS:
 >
 > ```php
 > <?= RecaptchaV2::widget()->withResponseFieldName('gRecaptchaResponse') ?>
 > ```
 >
 > ```php
 > #[RecaptchaV2Rule]
 > public string $gRecaptchaResponse = '';
 > ```

### реКАПЧА v3
```php
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3;

// siteKey comes from DI config (RecaptchaConfig.siteKeyV3)
echo RecaptchaV3::widget();
```
Виджет v3 отображает API `<script>`, скрытый ввод для токена и скрипт
, который заполняет токен при загрузке страницы (или, с помощью `withFormId()`, перехватывает отправку формы
, запускает `grecaptcha.execute()` с настроенным `action`, записывает токен
 в скрытый ввод, затем отправляет — "невидимую отправку"):

```php
echo RecaptchaV3::widget()
    ->withAction('login')
    ->withFieldName('recaptchaToken')   // hidden input name bound to the model attribute
    ->withFormId('login-form')          // optional: enable invisible-submit binding
    ->withBadge(RecaptchaV3Badge::Hidden); // optional: hide badge + render required legal notice
```
Если значок скрыт, вы должны оставить официальное уведомление reCAPTCHA видимым — виджет
 отобразит его за вас. Все значения закодированы в формате JSON с XSS-безопасными флагами перед внедрением
 во встроенный скрипт. @@ЛИНИЯ@@
```php
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Rule;

class LoginForm
{
    #[RecaptchaV3Rule(threshold: 0.5, action: 'login')]
    public string $recaptchaToken = '';
}
```
### Поля формы (модель-форма)
Для форм, отображаемых на сервере, `RecaptchaV2Field` / `RecaptchaV3Field` интегрируются с
 `yiisoft/form-model`: они привязывают токен к свойству модели и делегируют рендеринг
 виджетам (один путь рендеринга, поэтому виджет и поле остаются синхронизированными). @@ЛИНИЯ@@
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
### Политика безопасности контента
Оба виджета выдают встроенные `<script>` (и `<style>`). При строгом CSP без
 `unsafe-inline` передайте nonce — он применяется к каждому созданному тегу:

```php
echo RecaptchaV3::widget()->withNonce($cspNonce);
echo RecaptchaV3Field::field($form, 'recaptchaToken')->siteKey($key)->nonce($cspNonce);
```
### IP клиента за прокси
Когда sendRemoteIp включен, IP-адрес клиента разрешается через
 `ClientIpResolverInterface`. По умолчанию `RemoteAddrClientIpResolver` читается как
 `REMOTE_ADDR` (подтверждается с помощью `FILTER_VALIDATE_IP`) — правильно, только если вы
 не находитесь за прокси/CDN, или когда промежуточное программное обеспечение доверенных хостов уже нормализовало
 `REMOTE_ADDR`. За ненормализованным прокси привяжите свой собственный преобразователь:

```php
// config/common/di.php
ClientIpResolverInterface::class => static fn (MyIpDetector $d): ClientIpResolverInterface
    => new class ($d) implements ClientIpResolverInterface {
        public function __construct(private MyIpDetector $d) {}
        public function resolve(): ?string { return $this->d->detect(); }
    },
```
### Повторная попытка временных сбоев
`RecaptchaClient` принимает любой клиент PSR-18, поэтому вы можете обернуть его повторной попыткой.
decorator (e.g. [`rasuvaeff/retry`](https://github.com/rasuvaeff/retry)'s
`Http\RetryingHttpClient`), чтобы пережить временные сетевые ошибки — повторяйте попытку только при транспортных исключениях
, поскольку `siteverify` — это `POST`. Никаких изменений пакета не требуется;
 внедрить декорированный клиент, в который RecaptchaClient получает свой клиент PSR-18. @@ЛИНИЯ@@
### Внедрение зависимостей (Yii3)
Переопределить параметры в конфигурации вашего приложения:

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
| Язык | Файл |
 |--------|------|
 | `ру` | `messages/ru/yii3-recaptcha.php` |

 Чтобы добавить больше языков, создайте `messages/<locale>/yii3-recaptcha.php`:

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
 | `withSiteKey(строка $siteKey): self` | Ключ сайта Google (обязательно). |
 | `withId(строка $id): self` | Идентификатор DOM для контейнера виджетов. По умолчанию: уникальный идентификатор, создаваемый автоматически (поддерживается несколько виджетов на странице). |
 | `withTheme(RecaptchaV2Theme $theme): self` | «Светлый» или «Темный». По умолчанию: `Свет`. |
 | `withType(RecaptchaV2Type $type): self` | «Изображение» или «Аудио». По умолчанию: `Изображение`. |
 | `withSize(RecaptchaV2Size $size): self` | «Нормальный», «Компактный» или «Невидимый». По умолчанию: «Нормальный». |
 | `withJsApiUrl(строка $url): self` | Переопределить URL-адрес сценария. |
 | `withCallback(строка $cb): self` | Обратный вызов JS в случае успеха. |
 | `withExpiredCallback(строка $cb): self` | Обратный вызов JS по истечении срока действия. |
 | `withErrorCallback(строка $cb): self` | Обратный вызов JS при ошибке. |
 | `withNonce(строка $nonce): self` | CSP `nonce` для каждого созданного `<script>`. |
 | `render(): строка` | Возвращает HTML. Выдает MissingSiteKeyException, если siteKey не установлен. | @@ЛИНИЯ@@
### `RecaptchaV3` (виджет)
| Метод | Описание |
 |--------|-------------|
 | `withSiteKey(строка $siteKey): self` | Ключ сайта Google (обязательно). |
 | `withAction(string $action): self` | Имя действия, передаваемое в `grecaptcha.execute()`. По умолчанию: `отправить`. |
 | `withFieldName(строка $name): self` | Скрытое входное имя (атрибут модели). По умолчанию: `g-recaptcha-response`. |
 | `withFieldId(строка $id): self` | Скрытый входной идентификатор DOM. По умолчанию: автоматически сгенерированный уникальный идентификатор. |
 | `withFormId(строка $id): self` | Включите привязку невидимой отправки к этому идентификатору формы. По умолчанию: нет (токен заполняется при загрузке). |
 | `withBadge(RecaptchaV3Badge $badge): self` | Положение значка: «BottomRight» (по умолчанию), «BottomLeft» или «Hidden» (+ юридическое уведомление). |
 | `withJsApiUrl(строка $url): self` | Переопределить URL-адрес сценария. |
 | `withNonce(строка $nonce): self` | CSP `nonce` для каждого созданного `<script>`/`<style>`. |
 | `render(): строка` | Возвращает HTML (скрипт + скрытый ввод + встроенный скрипт). Выдает MissingSiteKeyException, если siteKey не установлен. | @@ЛИНИЯ@@
### `РекапчаКонфиг`
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
`verify()` использует `secretV2`, `verifyV3()` использует `secretV3` из конфигурации.
 `verifyWithSecret()` использует собственный секретный ключ (для правил v2/v3, которые его переопределяют).
 Клиент **никогда не выдает**: при сбое транспорта/HTTP/JSON он возвращает неудачный
 `VerificationResult` с тегом `TRANSPORT_ERROR` (закрытие при сбое). В конвейере валидатора
 обработчики разрешают `clientIp` через `ClientIpResolverInterface` — только
, когда оба правила `sendRemoteIp` и `RecaptchaConfig::sendRemoteIp` включены. @@ЛИНИЯ@@
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
### Поля, преобразователь, исключения
| Тип | Цель |
 |------|---------|
 | `Field\RecaptchaV2Field`, `Field\RecaptchaV3Field` | Поля `yiisoft/form-model` (`::field($model, $property)`), делегируют виджетам. |
 | `ClientIpResolverInterface` + `RemoteAddrClientIpResolver` | Подключаемое разрешение IP-адреса клиента; по умолчанию читает проверенный `REMOTE_ADDR`. |
 | `RecaptchaRegistry` | Статический запасной вариант (`configure(client, ipResolver?, Translation?)`) для построения обработчика без аргументов; заполняется бутстрапом. |
 | `Исключение\RecaptchaException` | Интерфейс маркера для всех исключений пакета. |
 | `Exception\MissingSiteKeyException` | Вызывается, когда виджет/поле отображается без ключа сайта. |
 | `Исключение\MissingClientException` | Вызывается, когда у обработчика нет клиента (ни внедренного, ни зарегистрированного). | @@ЛИНИЯ@@
### `RecaptchaV2Rule` / `RecaptchaV2RuleHandler`
| Параметр | Тип | По умолчанию | Описание |
 |-----------|------|---------|-------------|
 | `сообщение` | `строка` | `'Проверка CAPTCHA не удалась.'` | Сообщение об ошибке. |
 | `секрет` | `?строка` | `ноль` | Переопределить секрет. |
 | `sendRemoteIp` | `бул` | `ложь` | Переслать IP-адрес клиента. |
 | `failOpenOnError` | `бул` | `ложь` | Пройти проверку, если siteverify недоступен (ошибка транспорта). По умолчанию не закрывается. |
 | `skipOnEmpty` | `bool\|callable\|null` | `ноль` | Пропустить пустое. |
 | `skipOnError` | `бул` | `ложь` | Пропустить предыдущую ошибку. |
 | `когда` | `?Закрытие` | `ноль` | Условное исполнение. | @@ЛИНИЯ@@
### `RecaptchaV3Rule` / `RecaptchaV3RuleHandler`
То же, что и версия 2, плюс:

 | Параметр | Тип | По умолчанию | Описание |
 |-----------|------|---------|-------------|
 | `порог` | `плавать` | `0,5` | Минимальный балл в диапазоне «0.0..1.0». |
 | `действие` | `?строка` | `ноль` | Название ожидаемого действия. |
 | `scoreTooLowMessage` | `строка` | `'Показатель CAPTCHA слишком низкий.'` | Ошибка оценки. |
 | `actionMismatchMessage` | `строка` | `'Действие CAPTCHA не соответствует.'` | Ошибка действия. | @@ЛИНИЯ@@
### Перечисления
| Перечисление | Ценности |
 |------|--------|
 | `RecaptchaV2Theme` | `Светлый`, `Тёмный` |
 | `RecaptchaV2Type` | `Изображение`, `Аудио` |
 | `RecaptchaV2Size` | «Нормальный», «Компактный», «Невидимый» | @@ЛИНИЯ@@
## Безопасность
- Виджет отображает **публичные** ключи сайта в HTML — это сделано намеренно.
 — Секреты доступны только на стороне сервера.
 — проверка токена осуществляется по протоколу HTTPS.
 — порог оценки v3 и проверка действий предотвращают повторное использование токена в разных контекстах.
 — Widget JS построен с использованием `json_encode` с использованием `JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP`,
, поэтому имена обратного вызова, действия, идентификаторы и другие значения не могут выходить за пределы встроенного
 `<script>` (без конкатенации необработанных строк).
 — `sendRemoteIp` включен; IP-адрес клиента берется из ClientIpResolverInterface.
 По умолчанию используется `REMOTE_ADDR` (подтверждается с помощью `FILTER_VALIDATE_IP`), а не ввод пользователя
. За прокси-сервером/CDN привяжите преобразователь, поддерживающий прокси-сервер, чтобы использовался реальный IP-адрес клиента
 (см. [IP-адрес клиента за прокси-сервером](#client-ip-behind-a-proxy)).
 - **По умолчанию закрыто при сбое.** Если конечная точка siteverify недоступна, клиент
 возвращает неудачный результат (`TRANSPORT_ERROR`), и правило отклоняется — сбой
 не может молча пропустить ботов. Включите отказоустойчивое открытие для каждого правила с помощью
 `failOpenOnError: true`, когда доступность важнее строгости.
 — виджеты принимают nonce CSP через withNonce() для строгой политики безопасности контента. @@ЛИНИЯ@@
## Примеры
См. [examples/](examples/) для работоспособных сценариев.

 | Скрипт | Шоу | Нужен сервер? |
 |--------|-------|:-------------:|
 | [`widget-v2.php`](examples/widget-v2.php) | Рендеринг виджета v2 | нет |
 | [`widget-v3.php`](examples/widget-v3.php) | Рендеринг виджета v3 | нет | @@ЛИНИЯ@@
## Разработка
На хосте нет PHP/Composer — запустите в Docker через образ `composer:2`:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
```
`make test-coverage` и `makemutation` загружают `pcov` внутри Docker-контейнера
, потому что базовый образ `composer:2` не поставляется с драйвером покрытия.

 Или с помощью Make:

```bash
make install
make build
make cs-fix
make test
```
CI запускает `composer build` на PHP 8.3, 8.4 и 8.5. @@ЛИНИЯ@@
## Лицензия
[BSD-3-пункт](LICENSE.md)
