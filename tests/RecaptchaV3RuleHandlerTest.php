<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Classify;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Rasuvaeff\Understudy\Arg;
use Rasuvaeff\Understudy\Captor;
use Rasuvaeff\Understudy\Invocation;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3Recaptcha\AbstractRecaptchaRuleHandler;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3RuleHandler;
use Rasuvaeff\Yii3Recaptcha\RemoteAddrClientIpResolver;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\Validator\Exception\UnexpectedRuleException;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;

use function Rasuvaeff\Understudy\when;

#[Test]
#[Covers(RecaptchaV3Rule::class)]
#[Covers(RecaptchaV3RuleHandler::class)]
#[Covers(AbstractRecaptchaRuleHandler::class)]
final class RecaptchaV3RuleHandlerTest
{
    private RecaptchaV3RuleHandler $handler;

    /** @var Captor<RequestInterface> */
    private Captor $requests;

    private Response $mockResponse;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"login"}');
        $client = $this->createClient(new RecaptchaConfig(secretV3: 'test-secret-v3'));
        $this->handler = new RecaptchaV3RuleHandler(client: $client, ipResolver: new RemoteAddrClientIpResolver());
    }

    public function validTokenWithScorePasses(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"login"}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(action: 'login'), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function scoreBelowThresholdFails(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.3,"action":"login"}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(threshold: 0.5), new ValidationContext());

        Assert::false($result->isValid());
        Assert::true(stripos(implode(' ', $result->getErrorMessages()), 'score') !== false);
    }

    public function actionMismatchFails(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"submit"}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(action: 'login'), new ValidationContext());

        Assert::false($result->isValid());
        Assert::true(stripos(implode(' ', $result->getErrorMessages()), 'action') !== false);
    }

    public function noActionCheckWhenRuleActionIsNull(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"whatever"}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function apiFailureFails(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(), new ValidationContext());

        Assert::false($result->isValid());
    }

    public function emptyValueFails(): void
    {
        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('', new RecaptchaV3Rule(), $context);

        Assert::false($result->isValid());
        Assert::same($result->getErrors()[0]->getParameters(), ['property' => 'captcha']);
    }

    public function customThreshold(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.7,"action":"login"}');

        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('token', new RecaptchaV3Rule(threshold: 0.8, action: 'login'), $context);

        Assert::false($result->isValid());
        Assert::same(
            $result->getErrors()[0]->getParameters(),
            [
                'property' => 'captcha',
                'score' => '0.7',
                'threshold' => '0.8',
            ],
        );
    }

    public function customScoreTooLowMessage(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.1}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(scoreTooLowMessage: 'Low score!'), new ValidationContext());

        Assert::false($result->isValid());
        Assert::true(in_array('Low score!', $result->getErrorMessages(), strict: true));
    }

    public function customActionMismatchMessage(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"wrong"}');

        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('token', new RecaptchaV3Rule(actionMismatchMessage: 'Bad action!', action: 'login'), $context);

        Assert::false($result->isValid());
        Assert::true(in_array('Bad action!', $result->getErrorMessages(), strict: true));
        Assert::same(
            $result->getErrors()[0]->getParameters(),
            [
                'property' => 'captcha',
                'expected' => 'login',
                'actual' => 'wrong',
            ],
        );
    }

    public function ruleReturnsHandlerClass(): void
    {
        Assert::same((new RecaptchaV3Rule())->getHandler(), RecaptchaV3RuleHandler::class);
    }

    public function usesSecretV3FromConfig(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"login"}');

        $this->handler->validate('token', new RecaptchaV3Rule(action: 'login'), new ValidationContext());

        Assert::string($this->requests->last()->getBody()->__toString())->contains('secret=test-secret-v3');
    }

    public function apiFailureIncludesErrorCodesInParameters(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('token', new RecaptchaV3Rule(), $context);

        Assert::false($result->isValid());
        Assert::same(
            $result->getErrors()[0]->getParameters(),
            [
                'property' => 'captcha',
                'errorCodes' => 'invalid-input-response',
            ],
        );
    }

    public function sendRemoteIpPassesClientIpFromRequest(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"login"}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: ['REMOTE_ADDR' => '1.2.3.4']),
        );
        $handler = new RecaptchaV3RuleHandler(client: $this->createClient(new RecaptchaConfig(secretV3: 'test-secret-v3', sendRemoteIp: true)), ipResolver: new RemoteAddrClientIpResolver($requestProvider));

        $result = $handler->validate('token', new RecaptchaV3Rule(action: 'login', sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
        Assert::string($this->requests->last()->getBody()->__toString())->contains('remoteip=1.2.3.4');
    }

    public function omitsRemoteIpWhenRequestDoesNotContainStringAddress(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"login"}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: ['REMOTE_ADDR' => 123]),
        );
        $handler = new RecaptchaV3RuleHandler(client: $this->createClient(new RecaptchaConfig(secretV3: 'test-secret-v3', sendRemoteIp: true)), ipResolver: new RemoteAddrClientIpResolver($requestProvider));

        $result = $handler->validate('token', new RecaptchaV3Rule(action: 'login', sendRemoteIp: true), new ValidationContext());

        Assert::true($result->isValid());
        Assert::string($this->requests->last()->getBody()->__toString())->notContains('remoteip=');
    }

    public function throwsOnUnexpectedRule(): void
    {
        Expect::exception(UnexpectedRuleException::class);

        $rule = Understudy::for(RuleInterface::class);
        when(fn() => $rule->getHandler())->returns('fake-handler');

        $this->handler->validate('token', $rule, new ValidationContext());
    }

    public function scoreTooLowErrorContainsActualScoreInParameters(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.3,"action":"login"}');

        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('token', new RecaptchaV3Rule(threshold: 0.5), $context);

        Assert::false($result->isValid());
        $params = $result->getErrors()[0]->getParameters();
        Assert::same($params['score'], '0.3');
        Assert::same($params['threshold'], '0.5');
    }

    public function nullScoreUsesZeroInErrorParameters(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true}');

        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('token', new RecaptchaV3Rule(threshold: 0.5), $context);

        Assert::false($result->isValid());
        $params = $result->getErrors()[0]->getParameters();
        Assert::same($params['score'], '0');
    }

    public function scoreEqualToThresholdPasses(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.5,"action":"login"}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(threshold: 0.5), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function transportErrorFailsClosedByDefault(): void
    {
        $this->mockResponse = new Response(503, [], 'Service Unavailable');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(), new ValidationContext());

        Assert::false($result->isValid());
    }

    public function transportErrorFailsOpenWhenConfigured(): void
    {
        $this->mockResponse = new Response(503, [], 'Service Unavailable');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(failOpenOnError: true), new ValidationContext());

        Assert::true($result->isValid());
    }

    public function failOpenDoesNotApplyToGenuineVerdictFailure(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(failOpenOnError: true), new ValidationContext());

        Assert::false($result->isValid());
    }

    /**
     * For a genuine (non-transport) verdict with a matching action, the pass
     * decision is exactly `score >= threshold`: monotone in both, so raising
     * the threshold never turns a fail into a pass and raising the score never
     * turns a pass into a fail.
     */
    #[Property(runs: 300)]
    public function v3DecisionIsMonotoneInScoreAndThreshold(float $score, float $threshold): void
    {
        $this->mockResponse = new Response(
            200,
            [],
            (string) json_encode(['success' => true, 'score' => $score, 'action' => 'login']),
        );

        $result = $this->handler->validate(
            'token',
            new RecaptchaV3Rule(threshold: $threshold, action: 'login'),
            new ValidationContext(),
        );

        Classify::cover($score >= $threshold, 'passes the threshold', 30.0);
        Classify::cover($score < $threshold, 'fails the threshold', 30.0);

        Assert::same($result->isValid(), $score >= $threshold);
    }

    /**
     * @return iterable<string, array{float, float}>
     */
    public static function v3DecisionIsMonotoneInScoreAndThresholdExamples(): iterable
    {
        // The boundary is the contract: `>=`, not `>`. A score exactly at the
        // threshold must pass, and the two ends of the score range must not
        // be special-cased.
        yield 'exactly at the threshold' => [0.5, 0.5];
        yield 'a hair under the threshold' => [0.49, 0.5];
        yield 'lowest score, lowest threshold' => [0.0, 0.0];
        yield 'lowest score, any threshold' => [0.0, 0.5];
        yield 'highest score, highest threshold' => [1.0, 1.0];
    }

    /**
     * @return array<string, ArbitraryInterface>
     */
    public static function v3DecisionIsMonotoneInScoreAndThresholdGenerators(): array
    {
        return [
            'score' => Gen::floatBetween(0.0, 1.0),
            'threshold' => Gen::floatBetween(0.0, 1.0),
        ];
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
