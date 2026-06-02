<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaRegistry;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2RuleHandler;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3RuleHandler;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\SimpleMessageFormatter;
use Yiisoft\Translator\Translator;
use Yiisoft\Validator\ValidationContext;

#[CoversClass(RecaptchaRegistry::class)]
#[CoversClass(RecaptchaV2RuleHandler::class)]
#[CoversClass(RecaptchaV3RuleHandler::class)]
final class RecaptchaRegistryTest extends TestCase
{
    private RecaptchaClient $client;

    #[\Override]
    protected function setUp(): void
    {
        $config = new RecaptchaConfig(secretV2: 'test-secret', secretV3: 'test-secret-v3');
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(new Response(200, [], '{"success":true,"score":0.9,"action":"test"}'));
        $this->client = new RecaptchaClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);
    }

    #[\Override]
    protected function tearDown(): void
    {
        // reset registry between tests
        RecaptchaRegistry::configure(client: $this->client);
        RecaptchaRegistry::configure(client: $this->client, requestProvider: null, translator: null);
    }

    #[Test]
    public function registryReturnsNullBeforeConfiguration(): void
    {
        // Use reflection to reset static state
        $ref = new \ReflectionClass(RecaptchaRegistry::class);
        $ref->getProperty('client')->setValue(null, null);
        $ref->getProperty('requestProvider')->setValue(null, null);
        $ref->getProperty('translator')->setValue(null, null);

        $this->assertNull(RecaptchaRegistry::client());
        $this->assertNull(RecaptchaRegistry::requestProvider());
        $this->assertNull(RecaptchaRegistry::translator());
    }

    #[Test]
    public function registryStoresAndReturnsClient(): void
    {
        RecaptchaRegistry::configure(client: $this->client);

        $this->assertSame($this->client, RecaptchaRegistry::client());
    }

    #[Test]
    public function registryStoresOptionalDependencies(): void
    {
        $requestProvider = new RequestProvider();
        $translator = new Translator(locale: 'en');

        RecaptchaRegistry::configure(
            client: $this->client,
            requestProvider: $requestProvider,
            translator: $translator,
        );

        $this->assertSame($requestProvider, RecaptchaRegistry::requestProvider());
        $this->assertSame($translator, RecaptchaRegistry::translator());
    }

    #[Test]
    public function v2HandlerUsesRegistryClientWhenConstructedWithoutArgs(): void
    {
        RecaptchaRegistry::configure(client: $this->client);

        $handler = new RecaptchaV2RuleHandler();
        $result = $handler->validate('valid-token', new RecaptchaV2Rule(), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function v3HandlerUsesRegistryClientWhenConstructedWithoutArgs(): void
    {
        RecaptchaRegistry::configure(client: $this->client);

        $handler = new RecaptchaV3RuleHandler();
        $result = $handler->validate('valid-token', new RecaptchaV3Rule(), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function v2HandlerThrowsWhenClientNotAvailable(): void
    {
        $ref = new \ReflectionClass(RecaptchaRegistry::class);
        $ref->getProperty('client')->setValue(null, null);

        $handler = new RecaptchaV2RuleHandler();

        $this->expectException(\RuntimeException::class);
        $handler->validate('token', new RecaptchaV2Rule(), new ValidationContext());
    }

    #[Test]
    public function v3HandlerThrowsWhenClientNotAvailable(): void
    {
        $ref = new \ReflectionClass(RecaptchaRegistry::class);
        $ref->getProperty('client')->setValue(null, null);

        $handler = new RecaptchaV3RuleHandler();

        $this->expectException(\RuntimeException::class);
        $handler->validate('token', new RecaptchaV3Rule(), new ValidationContext());
    }

    #[Test]
    public function v2HandlerUsesRegistryTranslatorFallback(): void
    {
        $translator = new Translator(locale: 'en');
        $translator->addCategorySources(new CategorySource('yii3-recaptcha', new MessageSource(dirname(__DIR__) . '/messages'), new SimpleMessageFormatter()));
        RecaptchaRegistry::configure(client: $this->client, translator: $translator);

        $handler = new RecaptchaV2RuleHandler();
        $result = $handler->validate('', new RecaptchaV2Rule(), new ValidationContext());

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
    }

    #[Test]
    public function v3HandlerUsesRegistryTranslatorFallback(): void
    {
        $translator = new Translator(locale: 'en');
        $translator->addCategorySources(new CategorySource('yii3-recaptcha', new MessageSource(dirname(__DIR__) . '/messages'), new SimpleMessageFormatter()));
        RecaptchaRegistry::configure(client: $this->client, translator: $translator);

        $handler = new RecaptchaV3RuleHandler();
        $result = $handler->validate('', new RecaptchaV3Rule(), new ValidationContext());

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
    }

    #[Test]
    public function v2HandlerUsesRegistryRequestProviderFallback(): void
    {
        $requestProvider = new RequestProvider();
        RecaptchaRegistry::configure(client: $this->client, requestProvider: $requestProvider);

        $handler = new RecaptchaV2RuleHandler();
        $result = $handler->validate('valid-token', new RecaptchaV2Rule(sendRemoteIp: true), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function v3HandlerUsesRegistryRequestProviderFallback(): void
    {
        $requestProvider = new RequestProvider();
        RecaptchaRegistry::configure(client: $this->client, requestProvider: $requestProvider);

        $handler = new RecaptchaV3RuleHandler();
        $result = $handler->validate('valid-token', new RecaptchaV3Rule(sendRemoteIp: true), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function v2HandlerPrefersInjectedClientOverRegistry(): void
    {
        // registry has failing client
        $failingClient = $this->makeClient('{"success":false}');
        RecaptchaRegistry::configure(client: $failingClient);

        // handler constructed with passing client directly
        $handler = new RecaptchaV2RuleHandler(client: $this->client);
        $result = $handler->validate('token', new RecaptchaV2Rule(), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function v3HandlerPrefersInjectedClientOverRegistry(): void
    {
        $failingClient = $this->makeClient('{"success":false}');
        RecaptchaRegistry::configure(client: $failingClient);

        $handler = new RecaptchaV3RuleHandler(client: $this->client);
        $result = $handler->validate('token', new RecaptchaV3Rule(), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function v2HandlerPrefersInjectedTranslatorOverRegistry(): void
    {
        $registryTranslator = $this->createMock(\Yiisoft\Translator\TranslatorInterface::class);
        $registryTranslator->expects($this->never())->method('translate');

        $injectedTranslator = $this->createMock(\Yiisoft\Translator\TranslatorInterface::class);
        $injectedTranslator->method('translate')->willReturn('error');

        RecaptchaRegistry::configure(client: $this->client, translator: $registryTranslator);

        $handler = new RecaptchaV2RuleHandler(client: $this->client, translator: $injectedTranslator);
        $handler->validate('', new RecaptchaV2Rule(), new ValidationContext());
    }

    #[Test]
    public function v3HandlerPrefersInjectedTranslatorOverRegistry(): void
    {
        $registryTranslator = $this->createMock(\Yiisoft\Translator\TranslatorInterface::class);
        $registryTranslator->expects($this->never())->method('translate');

        $injectedTranslator = $this->createMock(\Yiisoft\Translator\TranslatorInterface::class);
        $injectedTranslator->method('translate')->willReturn('error');

        RecaptchaRegistry::configure(client: $this->client, translator: $registryTranslator);

        $handler = new RecaptchaV3RuleHandler(client: $this->client, translator: $injectedTranslator);
        $handler->validate('', new RecaptchaV3Rule(), new ValidationContext());
    }

    #[Test]
    public function v2HandlerPrefersInjectedRequestProviderOverRegistry(): void
    {
        $registryProvider = $this->createMock(RequestProviderInterface::class);
        $registryProvider->expects($this->never())->method('get');

        $injectedProvider = $this->createMock(RequestProviderInterface::class);
        $injectedProvider->method('get')->willThrowException(new \Yiisoft\RequestProvider\RequestNotSetException());

        RecaptchaRegistry::configure(client: $this->client, requestProvider: $registryProvider);

        $handler = new RecaptchaV2RuleHandler(client: $this->client, requestProvider: $injectedProvider);
        $handler->validate('token', new RecaptchaV2Rule(sendRemoteIp: true), new ValidationContext());
    }

    #[Test]
    public function v3HandlerPrefersInjectedRequestProviderOverRegistry(): void
    {
        $registryProvider = $this->createMock(RequestProviderInterface::class);
        $registryProvider->expects($this->never())->method('get');

        $injectedProvider = $this->createMock(RequestProviderInterface::class);
        $injectedProvider->method('get')->willThrowException(new \Yiisoft\RequestProvider\RequestNotSetException());

        RecaptchaRegistry::configure(client: $this->client, requestProvider: $registryProvider);

        $handler = new RecaptchaV3RuleHandler(client: $this->client, requestProvider: $injectedProvider);
        $handler->validate('token', new RecaptchaV3Rule(sendRemoteIp: true), new ValidationContext());
    }

    private function makeClient(string $responseBody): RecaptchaClient
    {
        $config = new RecaptchaConfig(secretV2: 'test-secret', secretV3: 'test-secret-v3');
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(new Response(200, [], $responseBody));

        return new RecaptchaClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);
    }
}
