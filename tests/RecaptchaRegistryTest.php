<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaRegistry;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2RuleHandler;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3RuleHandler;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\SimpleMessageFormatter;
use Yiisoft\Translator\Translator;
use Yiisoft\Validator\ValidationContext;

#[Test]
#[Covers(RecaptchaRegistry::class)]
#[Covers(RecaptchaV2RuleHandler::class)]
#[Covers(RecaptchaV3RuleHandler::class)]
final class RecaptchaRegistryTest
{
    private RecaptchaClient $client;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->client = $this->makeClient('{"success":true,"score":0.9,"action":"test"}');
    }

    #[AfterTest]
    public function tearDown(): void
    {
        RecaptchaRegistry::configure(client: $this->client);
        RecaptchaRegistry::configure(client: $this->client, requestProvider: null, translator: null);
    }

    public function registryReturnsNullBeforeConfiguration(): void
    {
        $this->resetRegistry();

        Assert::null(RecaptchaRegistry::client());
        Assert::null(RecaptchaRegistry::requestProvider());
        Assert::null(RecaptchaRegistry::translator());
    }

    public function registryStoresAndReturnsClient(): void
    {
        RecaptchaRegistry::configure(client: $this->client);

        Assert::same(RecaptchaRegistry::client(), $this->client);
    }

    public function registryStoresOptionalDependencies(): void
    {
        $requestProvider = new RequestProvider();
        $translator = new Translator(locale: 'en');

        RecaptchaRegistry::configure(
            client: $this->client,
            requestProvider: $requestProvider,
            translator: $translator,
        );

        Assert::same(RecaptchaRegistry::requestProvider(), $requestProvider);
        Assert::same(RecaptchaRegistry::translator(), $translator);
    }

    public function v2HandlerUsesRegistryClientWhenConstructedWithoutArgs(): void
    {
        RecaptchaRegistry::configure(client: $this->client);

        $handler = new RecaptchaV2RuleHandler();
        $result = $handler->validate('valid-token', new RecaptchaV2Rule(), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function v3HandlerUsesRegistryClientWhenConstructedWithoutArgs(): void
    {
        RecaptchaRegistry::configure(client: $this->client);

        $handler = new RecaptchaV3RuleHandler();
        $result = $handler->validate('valid-token', new RecaptchaV3Rule(), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function v2HandlerThrowsWhenClientNotAvailable(): void
    {
        $this->resetRegistry();

        $handler = new RecaptchaV2RuleHandler();

        Expect::exception(\RuntimeException::class);
        $handler->validate('token', new RecaptchaV2Rule(), new ValidationContext());
    }

    public function v3HandlerThrowsWhenClientNotAvailable(): void
    {
        $this->resetRegistry();

        $handler = new RecaptchaV3RuleHandler();

        Expect::exception(\RuntimeException::class);
        $handler->validate('token', new RecaptchaV3Rule(), new ValidationContext());
    }

    public function v2HandlerUsesRegistryTranslatorFallback(): void
    {
        $translator = new Translator(locale: 'en');
        $translator->addCategorySources(new CategorySource('yii3-recaptcha', new MessageSource(dirname(__DIR__) . '/messages'), new SimpleMessageFormatter()));
        RecaptchaRegistry::configure(client: $this->client, translator: $translator);

        $handler = new RecaptchaV2RuleHandler();
        $result = $handler->validate('', new RecaptchaV2Rule(), new ValidationContext());

        Assert::false($result->isValid());
        Assert::true($result->getErrors() !== []);
    }

    public function v3HandlerUsesRegistryTranslatorFallback(): void
    {
        $translator = new Translator(locale: 'en');
        $translator->addCategorySources(new CategorySource('yii3-recaptcha', new MessageSource(dirname(__DIR__) . '/messages'), new SimpleMessageFormatter()));
        RecaptchaRegistry::configure(client: $this->client, translator: $translator);

        $handler = new RecaptchaV3RuleHandler();
        $result = $handler->validate('', new RecaptchaV3Rule(), new ValidationContext());

        Assert::false($result->isValid());
        Assert::true($result->getErrors() !== []);
    }

    public function v2HandlerUsesRegistryRequestProviderFallback(): void
    {
        $requestProvider = new RequestProvider();
        RecaptchaRegistry::configure(client: $this->client, requestProvider: $requestProvider);

        $handler = new RecaptchaV2RuleHandler();
        $result = $handler->validate('valid-token', new RecaptchaV2Rule(sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function v3HandlerUsesRegistryRequestProviderFallback(): void
    {
        $requestProvider = new RequestProvider();
        RecaptchaRegistry::configure(client: $this->client, requestProvider: $requestProvider);

        $handler = new RecaptchaV3RuleHandler();
        $result = $handler->validate('valid-token', new RecaptchaV3Rule(sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function v2HandlerPrefersInjectedClientOverRegistry(): void
    {
        $failingClient = $this->makeClient('{"success":false}');
        RecaptchaRegistry::configure(client: $failingClient);

        $handler = new RecaptchaV2RuleHandler(client: $this->client);
        $result = $handler->validate('token', new RecaptchaV2Rule(), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function v3HandlerPrefersInjectedClientOverRegistry(): void
    {
        $failingClient = $this->makeClient('{"success":false}');
        RecaptchaRegistry::configure(client: $failingClient);

        $handler = new RecaptchaV3RuleHandler(client: $this->client);
        $result = $handler->validate('token', new RecaptchaV3Rule(), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function v2HandlerPrefersInjectedTranslatorOverRegistry(): void
    {
        $registryTranslator = new FakeTranslator(throw: true);
        $injectedTranslator = new FakeTranslator(return: 'error');

        RecaptchaRegistry::configure(client: $this->client, translator: $registryTranslator);

        $handler = new RecaptchaV2RuleHandler(client: $this->client, translator: $injectedTranslator);
        $handler->validate('', new RecaptchaV2Rule(), new ValidationContext());

        Assert::same($registryTranslator->callCount, 0);
        Assert::same($injectedTranslator->callCount, 1);
    }

    public function v3HandlerPrefersInjectedTranslatorOverRegistry(): void
    {
        $registryTranslator = new FakeTranslator(throw: true);
        $injectedTranslator = new FakeTranslator(return: 'error');

        RecaptchaRegistry::configure(client: $this->client, translator: $registryTranslator);

        $handler = new RecaptchaV3RuleHandler(client: $this->client, translator: $injectedTranslator);
        $handler->validate('', new RecaptchaV3Rule(), new ValidationContext());

        Assert::same($registryTranslator->callCount, 0);
        Assert::same($injectedTranslator->callCount, 1);
    }

    public function v2HandlerPrefersInjectedRequestProviderOverRegistry(): void
    {
        $registryProvider = new FakeRequestProvider(throw: true);
        $injectedProvider = new FakeRequestProvider(throw: true);

        RecaptchaRegistry::configure(client: $this->client, requestProvider: $registryProvider);

        $handler = new RecaptchaV2RuleHandler(client: $this->client, requestProvider: $injectedProvider);
        $handler->validate('token', new RecaptchaV2Rule(sendRemoteIp: true), new ValidationContext());

        Assert::same($registryProvider->callCount, 0);
        Assert::same($injectedProvider->callCount, 1);
    }

    public function v3HandlerPrefersInjectedRequestProviderOverRegistry(): void
    {
        $registryProvider = new FakeRequestProvider(throw: true);
        $injectedProvider = new FakeRequestProvider(throw: true);

        RecaptchaRegistry::configure(client: $this->client, requestProvider: $registryProvider);

        $handler = new RecaptchaV3RuleHandler(client: $this->client, requestProvider: $injectedProvider);
        $handler->validate('token', new RecaptchaV3Rule(sendRemoteIp: true), new ValidationContext());

        Assert::same($registryProvider->callCount, 0);
        Assert::same($injectedProvider->callCount, 1);
    }

    private function resetRegistry(): void
    {
        $ref = new \ReflectionClass(RecaptchaRegistry::class);
        $ref->getProperty('client')->setValue(null, null);
        $ref->getProperty('requestProvider')->setValue(null, null);
        $ref->getProperty('translator')->setValue(null, null);
    }

    private function makeClient(string $responseBody): RecaptchaClient
    {
        $config = new RecaptchaConfig(secretV2: 'test-secret', secretV3: 'test-secret-v3');
        $psr17 = new Psr17Factory();
        $httpClient = (new FakeHttpClient())
            ->withCallback(static fn(RequestInterface $request): Response => new Response(200, [], $responseBody));

        return new RecaptchaClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);
    }
}
