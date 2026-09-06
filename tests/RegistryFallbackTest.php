<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\Understudy\Arg;
use Rasuvaeff\Understudy\Captor;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3Recaptcha\AbstractRecaptchaRuleHandler;
use Rasuvaeff\Yii3Recaptcha\ClientIpResolverInterface;
use Rasuvaeff\Yii3Recaptcha\Exception\MissingClientException;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaRegistry;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2RuleHandler;
use ReflectionClass;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\SimpleMessageFormatter;
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidationContext;

use function Rasuvaeff\Understudy\when;

/**
 * Covers the no-arg construction path (default SimpleRuleHandlerContainer),
 * where the handler falls back to RecaptchaRegistry.
 */
#[Test]
#[Covers(RecaptchaRegistry::class)]
#[Covers(RecaptchaV2RuleHandler::class)]
#[Covers(AbstractRecaptchaRuleHandler::class)]
#[Covers(MissingClientException::class)]
final class RegistryFallbackTest
{
    /** @var Captor<RequestInterface> */
    private Captor $requests;

    #[BeforeTest]
    #[AfterTest]
    public function resetRegistry(): void
    {
        $refl = new ReflectionClass(RecaptchaRegistry::class);
        foreach (['client', 'ipResolver', 'translator'] as $prop) {
            $refl->getProperty($prop)->setValue(null, null);
        }
        $this->requests = Arg::captor(RequestInterface::class);
    }

    public function noArgHandlerUsesRegistryClient(): void
    {
        RecaptchaRegistry::configure(client: $this->client('{"success":true}'));

        $result = (new RecaptchaV2RuleHandler())->validate('token', new RecaptchaV2Rule(), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function noArgHandlerWithoutRegistryThrows(): void
    {
        Expect::exception(MissingClientException::class)
            ->withMessage('RecaptchaClient is not available. Register the package config (bootstrap) or a container-backed rule-handler resolver.');

        (new RecaptchaV2RuleHandler())->validate('token', new RecaptchaV2Rule(), new ValidationContext());
    }

    public function usesRegistryIpResolver(): void
    {
        RecaptchaRegistry::configure(
            client: $this->client('{"success":true}'),
            ipResolver: $this->ipResolver('5.6.7.8'),
        );

        (new RecaptchaV2RuleHandler())->validate('token', new RecaptchaV2Rule(sendRemoteIp: true), new ValidationContext());

        Assert::string($this->requests->last()->getBody()->__toString())->contains('remoteip=5.6.7.8');
    }

    public function fallsBackToRemoteAddrResolverWhenNoneConfigured(): void
    {
        RecaptchaRegistry::configure(client: $this->client('{"success":true}'));

        $result = (new RecaptchaV2RuleHandler())->validate('token', new RecaptchaV2Rule(sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
        // Default RemoteAddrClientIpResolver with no request → no remoteip sent.
        Assert::string($this->requests->last()->getBody()->__toString())->notContains('remoteip=');
    }

    public function usesRegistryTranslator(): void
    {
        $translator = new Translator(locale: 'ru');
        $translator->addCategorySources(new CategorySource(
            'yii3-recaptcha',
            new MessageSource(dirname(__DIR__) . '/messages'),
            extension_loaded('intl') ? new IntlMessageFormatter() : new SimpleMessageFormatter(),
        ));
        RecaptchaRegistry::configure(client: $this->client('{"success":false}'), translator: $translator);

        $result = (new RecaptchaV2RuleHandler())->validate('token', new RecaptchaV2Rule(), new ValidationContext());

        Assert::false($result->isValid());
        Assert::true(in_array('Проверка CAPTCHA не удалась.', $result->getErrorMessages(), strict: true));
    }

    public function injectedClientTakesPriorityOverRegistryClient(): void
    {
        RecaptchaRegistry::configure(client: $this->client('{"success":false}'));

        $handler = new RecaptchaV2RuleHandler(client: $this->client('{"success":true}'));
        $result = $handler->validate('token', new RecaptchaV2Rule(), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function injectedIpResolverTakesPriorityOverRegistryIpResolver(): void
    {
        RecaptchaRegistry::configure(
            client: $this->client('{"success":true}'),
            ipResolver: $this->ipResolver('9.9.9.9'),
        );

        $handler = new RecaptchaV2RuleHandler(
            client: $this->client('{"success":true}'),
            ipResolver: $this->ipResolver('1.1.1.1'),
        );
        $handler->validate('token', new RecaptchaV2Rule(sendRemoteIp: true), new ValidationContext());

        Assert::string($this->requests->last()->getBody()->__toString())->contains('remoteip=1.1.1.1');
        Assert::string($this->requests->last()->getBody()->__toString())->notContains('remoteip=9.9.9.9');
    }

    public function injectedTranslatorTakesPriorityOverRegistryTranslator(): void
    {
        RecaptchaRegistry::configure(
            client: $this->client('{"success":true}'),
            translator: $this->translator('registry-message'),
        );

        $handler = new RecaptchaV2RuleHandler(
            client: $this->client('{"success":false}'),
            translator: $this->translator('injected-message'),
        );
        $result = $handler->validate('token', new RecaptchaV2Rule(), new ValidationContext());

        Assert::false($result->isValid());
        Assert::true(in_array('injected-message', $result->getErrorMessages(), strict: true));
    }

    private function client(string $body): RecaptchaClient
    {
        $psr17 = new Psr17Factory();
        $httpClient = Understudy::for(ClientInterface::class);

        when(fn() => $httpClient->sendRequest($this->requests->capture()))
            ->returns(new Response(200, [], $body));

        return new RecaptchaClient(
            config: new RecaptchaConfig(secretV2: 'test-secret', sendRemoteIp: true),
            httpClient: $httpClient,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );
    }

    private function ipResolver(string $ip): ClientIpResolverInterface
    {
        $resolver = Understudy::for(ClientIpResolverInterface::class);
        when(fn() => $resolver->resolve())->returns($ip);

        return $resolver;
    }

    private function translator(string $message): TranslatorInterface
    {
        $translator = Understudy::for(TranslatorInterface::class);
        when(fn() => $translator->translate(Arg::any(), Arg::any(), Arg::any()))->returns($message);

        return $translator;
    }
}
