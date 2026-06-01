<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2RuleHandler;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\SimpleMessageFormatter;
use Yiisoft\Translator\Translator;
use Yiisoft\Validator\ValidationContext;

#[CoversClass(RecaptchaV2Rule::class)]
#[CoversClass(RecaptchaV2RuleHandler::class)]
final class RecaptchaV2RuleHandlerTest extends TestCase
{
    private RecaptchaV2RuleHandler $handler;
    private RecaptchaClient $client;
    private ?RequestInterface $lastRequest = null;
    private Response $mockResponse;

    #[\Override]
    protected function setUp(): void
    {
        $config = new RecaptchaConfig(secretV2: 'test-secret', sendRemoteIp: true);
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return $this->mockResponse;
            },
        );
        $this->client = new RecaptchaClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);
        $this->handler = new RecaptchaV2RuleHandler(client: $this->client);
    }

    #[Test]
    public function validTokenPasses(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $result = $this->handler->validate('valid-token', new RecaptchaV2Rule(), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function invalidTokenFails(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->handler->validate('bad-token', new RecaptchaV2Rule(), new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    #[Test]
    public function emptyValueFails(): void
    {
        $result = $this->handler->validate('', new RecaptchaV2Rule(), new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    #[Test]
    public function customMessageIsUsed(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false}');

        $result = $this->handler->validate('token', new RecaptchaV2Rule(message: 'Custom error'), new ValidationContext());

        $this->assertFalse($result->isValid());
        $this->assertContains('Custom error', $result->getErrorMessages());
    }

    #[Test]
    public function sendRemoteIpPassesClientIpFromRequest(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: ['REMOTE_ADDR' => '1.2.3.4']),
        );
        $handler = new RecaptchaV2RuleHandler(client: $this->client, requestProvider: $requestProvider);

        $result = $handler->validate('token', new RecaptchaV2Rule(sendRemoteIp: true), new ValidationContext());

        $this->assertTrue($result->isValid());
        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('remoteip=1.2.3.4', $body);
    }

    #[Test]
    public function secretOverrideUsesCustomSecret(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $this->handler->validate('token', new RecaptchaV2Rule(secret: 'override-secret'), new ValidationContext());

        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('secret=override-secret', $body);
    }

    #[Test]
    public function ruleReturnsHandlerClass(): void
    {
        $this->assertSame(RecaptchaV2RuleHandler::class, (new RecaptchaV2Rule())->getHandler());
    }

    #[Test]
    public function translatorTranslatesErrorMessage(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false}');

        $translator = new Translator(locale: 'ru');
        $categorySource = new CategorySource(
            'yii3-recaptcha',
            new MessageSource(dirname(__DIR__) . '/messages'),
            extension_loaded('intl')
                ? new IntlMessageFormatter()
                : new SimpleMessageFormatter(),
        );
        $translator->addCategorySources($categorySource);

        $config = new RecaptchaConfig(secretV2: 'test-secret');
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn($this->mockResponse);
        $client = new RecaptchaClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);

        $handler = new RecaptchaV2RuleHandler(client: $client, translator: $translator, translationCategory: 'yii3-recaptcha');

        $result = $handler->validate('token', new RecaptchaV2Rule(), new ValidationContext());

        $this->assertFalse($result->isValid());
        $this->assertContains('Проверка CAPTCHA не удалась.', $result->getErrorMessages());
    }
}
