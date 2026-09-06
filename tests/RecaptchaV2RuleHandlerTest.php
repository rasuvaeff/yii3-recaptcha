<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\Understudy\Arg;
use Rasuvaeff\Understudy\Captor;
use Rasuvaeff\Understudy\Invocation;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3Recaptcha\AbstractRecaptchaRuleHandler;
use Rasuvaeff\Yii3Recaptcha\ClientIpResolverInterface;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2RuleHandler;
use Rasuvaeff\Yii3Recaptcha\RemoteAddrClientIpResolver;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\SimpleMessageFormatter;
use Yiisoft\Translator\Translator;
use Yiisoft\Validator\Exception\UnexpectedRuleException;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;

use function Rasuvaeff\Understudy\when;

#[Test]
#[Covers(RecaptchaV2Rule::class)]
#[Covers(RecaptchaV2RuleHandler::class)]
#[Covers(AbstractRecaptchaRuleHandler::class)]
final class RecaptchaV2RuleHandlerTest
{
    private RecaptchaV2RuleHandler $handler;

    private RecaptchaClient $client;

    /** @var Captor<RequestInterface> */
    private Captor $requests;

    private Response $mockResponse;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');
        $this->client = $this->createClient(new RecaptchaConfig(secretV2: 'test-secret', sendRemoteIp: true));
        $this->handler = new RecaptchaV2RuleHandler(client: $this->client, ipResolver: new RemoteAddrClientIpResolver());
    }

    public function validTokenPasses(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $result = $this->handler->validate('valid-token', new RecaptchaV2Rule(), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function invalidTokenFails(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->handler->validate('bad-token', new RecaptchaV2Rule(), new ValidationContext());

        Assert::false($result->isValid());
    }

    public function emptyValueFails(): void
    {
        $result = $this->handler->validate('', new RecaptchaV2Rule(), new ValidationContext());

        Assert::false($result->isValid());
    }

    public function customMessageIsUsed(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false}');

        $result = $this->handler->validate('token', new RecaptchaV2Rule(message: 'Custom error'), new ValidationContext());

        Assert::false($result->isValid());
        Assert::true(in_array('Custom error', $result->getErrorMessages(), strict: true));
    }

    public function sendRemoteIpPassesClientIpFromRequest(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: ['REMOTE_ADDR' => '1.2.3.4']),
        );
        $handler = new RecaptchaV2RuleHandler(client: $this->client, ipResolver: new RemoteAddrClientIpResolver($requestProvider));

        $result = $handler->validate('token', new RecaptchaV2Rule(sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
        Assert::string($this->requests->last()->getBody()->__toString())->contains('remoteip=1.2.3.4');
    }

    public function ruleSendRemoteIpFalseNeverConsultsResolverEvenWhenConfigSendsRemoteIp(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $client = $this->createClient(new RecaptchaConfig(secretV2: 'test-secret', sendRemoteIp: true));
        // The rule says sendRemoteIp: false, so the resolver must never be
        // consulted: a strict double fails the test at the call.
        $ipResolver = Understudy::for(ClientIpResolverInterface::class);
        Understudy::strict($ipResolver);
        $handler = new RecaptchaV2RuleHandler(client: $client, ipResolver: $ipResolver);

        $result = $handler->validate('token', new RecaptchaV2Rule(sendRemoteIp: false), new ValidationContext());

        Assert::true($result->isValid());
        Assert::string($this->requests->last()->getBody()->__toString())->notContains('remoteip=');
    }

    public function secretOverrideUsesCustomSecret(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $this->handler->validate('token', new RecaptchaV2Rule(secret: 'override-secret'), new ValidationContext());

        Assert::string($this->requests->last()->getBody()->__toString())->contains('secret=override-secret');
    }

    public function ruleReturnsHandlerClass(): void
    {
        Assert::same((new RecaptchaV2Rule())->getHandler(), RecaptchaV2RuleHandler::class);
    }

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

        $client = $this->createClient(new RecaptchaConfig(secretV2: 'test-secret'));

        $handler = new RecaptchaV2RuleHandler(client: $client, ipResolver: new RemoteAddrClientIpResolver(), translator: $translator, translationCategory: 'yii3-recaptcha');

        $result = $handler->validate('token', new RecaptchaV2Rule(), new ValidationContext());

        Assert::false($result->isValid());
        Assert::true(in_array('Проверка CAPTCHA не удалась.', $result->getErrorMessages(), strict: true));
    }

    public function throwsOnUnexpectedRule(): void
    {
        Expect::exception(UnexpectedRuleException::class);

        $rule = Understudy::for(RuleInterface::class);
        when(fn() => $rule->getHandler())->returns('fake-handler');

        $this->handler->validate('token', $rule, new ValidationContext());
    }

    public function emptyValueErrorContainsPropertyParameter(): void
    {
        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('', new RecaptchaV2Rule(), $context);

        Assert::false($result->isValid());
        Assert::same($result->getErrors()[0]->getParameters(), ['property' => 'captcha']);
    }

    public function verificationFailureErrorContainsAllParameters(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('token', new RecaptchaV2Rule(), $context);

        Assert::false($result->isValid());
        Assert::same(
            $result->getErrors()[0]->getParameters(),
            ['property' => 'captcha', 'errorCodes' => 'invalid-input-response'],
        );
    }

    public function resolveClientIpReturnsNullWhenRemoteAddrNotSet(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: []),
        );
        $handler = new RecaptchaV2RuleHandler(client: $this->client, ipResolver: new RemoteAddrClientIpResolver($requestProvider));

        $result = $handler->validate('token', new RecaptchaV2Rule(sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
        Assert::string($this->requests->last()->getBody()->__toString())->notContains('remoteip=');
    }

    public function omitsRemoteIpWhenRequestDoesNotContainStringAddress(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: ['REMOTE_ADDR' => 123]),
        );
        $handler = new RecaptchaV2RuleHandler(client: $this->client, ipResolver: new RemoteAddrClientIpResolver($requestProvider));

        $result = $handler->validate('token', new RecaptchaV2Rule(sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
        Assert::string($this->requests->last()->getBody()->__toString())->notContains('remoteip=');
    }

    public function transportErrorFailsClosedByDefault(): void
    {
        $this->mockResponse = new Response(503, [], 'Service Unavailable');

        $result = $this->handler->validate('token', new RecaptchaV2Rule(), new ValidationContext());

        Assert::false($result->isValid());
    }

    public function transportErrorFailsOpenWhenConfigured(): void
    {
        $this->mockResponse = new Response(503, [], 'Service Unavailable');

        $result = $this->handler->validate('token', new RecaptchaV2Rule(failOpenOnError: true), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function failOpenDoesNotApplyToGenuineVerdictFailure(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->handler->validate('token', new RecaptchaV2Rule(failOpenOnError: true), new ValidationContext());

        Assert::false($result->isValid());
    }

    private function createClient(RecaptchaConfig $config): RecaptchaClient
    {
        $psr17 = new Psr17Factory();
        $httpClient = Understudy::for(ClientInterface::class);
        $this->requests = Arg::captor(RequestInterface::class);

        when(fn() => $httpClient->sendRequest($this->requests->capture()))
            ->answers(fn(Invocation $call): Response => $this->mockResponse);

        return new RecaptchaClient(
            config: $config,
            httpClient: $httpClient,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );
    }
}
