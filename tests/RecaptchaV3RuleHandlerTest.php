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
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3RuleHandler;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\Validator\Exception\UnexpectedRuleException;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;

#[CoversClass(RecaptchaV3Rule::class)]
#[CoversClass(RecaptchaV3RuleHandler::class)]
final class RecaptchaV3RuleHandlerTest extends TestCase
{
    private RecaptchaV3RuleHandler $handler;
    private ?RequestInterface $lastRequest = null;
    private Response $mockResponse;

    #[\Override]
    protected function setUp(): void
    {
        $config = new RecaptchaConfig(secretV3: 'test-secret-v3');
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return $this->mockResponse;
            },
        );
        $client = new RecaptchaClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);
        $this->handler = new RecaptchaV3RuleHandler(client: $client);
    }

    #[Test]
    public function validTokenWithScorePasses(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"login"}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(action: 'login'), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function scoreBelowThresholdFails(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.3,"action":"login"}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(threshold: 0.5), new ValidationContext());

        $this->assertFalse($result->isValid());
        $this->assertStringContainsStringIgnoringCase('score', implode(' ', $result->getErrorMessages()));
    }

    #[Test]
    public function actionMismatchFails(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"submit"}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(action: 'login'), new ValidationContext());

        $this->assertFalse($result->isValid());
        $this->assertStringContainsStringIgnoringCase('action', implode(' ', $result->getErrorMessages()));
    }

    #[Test]
    public function noActionCheckWhenRuleActionIsNull(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"whatever"}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(), new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function apiFailureFails(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(), new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    #[Test]
    public function emptyValueFails(): void
    {
        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('', new RecaptchaV3Rule(), $context);

        $this->assertFalse($result->isValid());
        $this->assertSame(['property' => 'captcha'], $result->getErrors()[0]->getParameters());
    }

    #[Test]
    public function customThreshold(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.7,"action":"login"}');

        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('token', new RecaptchaV3Rule(threshold: 0.8, action: 'login'), $context);

        $this->assertFalse($result->isValid());
        $this->assertSame(
            [
                'property' => 'captcha',
                'score' => '0.7',
                'threshold' => '0.8',
            ],
            $result->getErrors()[0]->getParameters(),
        );
    }

    #[Test]
    public function customScoreTooLowMessage(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.1}');

        $result = $this->handler->validate('token', new RecaptchaV3Rule(scoreTooLowMessage: 'Low score!'), new ValidationContext());

        $this->assertFalse($result->isValid());
        $this->assertContains('Low score!', $result->getErrorMessages());
    }

    #[Test]
    public function customActionMismatchMessage(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"wrong"}');

        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('token', new RecaptchaV3Rule(action: 'login', actionMismatchMessage: 'Bad action!'), $context);

        $this->assertFalse($result->isValid());
        $this->assertContains('Bad action!', $result->getErrorMessages());
        $this->assertSame(
            [
                'property' => 'captcha',
                'expected' => 'login',
                'actual' => 'wrong',
            ],
            $result->getErrors()[0]->getParameters(),
        );
    }

    #[Test]
    public function ruleReturnsHandlerClass(): void
    {
        $this->assertSame(RecaptchaV3RuleHandler::class, (new RecaptchaV3Rule())->getHandler());
    }

    #[Test]
    public function usesSecretV3FromConfig(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"login"}');

        $this->handler->validate('token', new RecaptchaV3Rule(action: 'login'), new ValidationContext());

        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('secret=test-secret-v3', $body);
    }

    #[Test]
    public function apiFailureIncludesErrorCodesInParameters(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $context = (new ValidationContext())->setPropertyLabel('captcha');
        $result = $this->handler->validate('token', new RecaptchaV3Rule(), $context);

        $this->assertFalse($result->isValid());
        $this->assertSame(
            [
                'property' => 'captcha',
                'errorCodes' => 'invalid-input-response',
            ],
            $result->getErrors()[0]->getParameters(),
        );
    }

    #[Test]
    public function sendRemoteIpPassesClientIpFromRequest(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"login"}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: ['REMOTE_ADDR' => '1.2.3.4']),
        );
        $handler = new RecaptchaV3RuleHandler(client: $this->createClient(sendRemoteIp: true), requestProvider: $requestProvider);

        $result = $handler->validate('token', new RecaptchaV3Rule(action: 'login', sendRemoteIp: true), new ValidationContext());

        $this->assertTrue($result->isValid());
        $this->assertNotNull($this->lastRequest);
        $this->assertStringContainsString('remoteip=1.2.3.4', $this->lastRequest->getBody()->__toString());
    }

    #[Test]
    public function omitsRemoteIpWhenRequestDoesNotContainStringAddress(): void
    {
        $this->mockResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"login"}');

        $requestProvider = new RequestProvider(
            new ServerRequest('POST', 'http://app.test', serverParams: ['REMOTE_ADDR' => 123]),
        );
        $handler = new RecaptchaV3RuleHandler(client: $this->createClient(sendRemoteIp: true), requestProvider: $requestProvider);

        $result = $handler->validate('token', new RecaptchaV3Rule(action: 'login', sendRemoteIp: true), new ValidationContext());

        $this->assertTrue($result->isValid());
        $this->assertNotNull($this->lastRequest);
        $this->assertStringNotContainsString('remoteip=', $this->lastRequest->getBody()->__toString());
    }

    #[Test]
    public function throwsOnUnexpectedRule(): void
    {
        $this->expectException(UnexpectedRuleException::class);

        $this->handler->validate('token', $this->createMock(RuleInterface::class), new ValidationContext());
    }

    private function createClient(bool $sendRemoteIp = false): RecaptchaClient
    {
        $config = new RecaptchaConfig(secretV3: 'test-secret-v3', sendRemoteIp: $sendRemoteIp);
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return $this->mockResponse;
            },
        );

        return new RecaptchaClient(
            config: $config,
            httpClient: $httpClient,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );
    }
}
