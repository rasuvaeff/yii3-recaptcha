<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;

#[CoversClass(RecaptchaClient::class)]
final class RecaptchaClientTest extends TestCase
{
    private RecaptchaClient $client;
    private ?RequestInterface $lastRequest = null;
    private Response $currentResponse;

    #[\Override]
    protected function setUp(): void
    {
        $config = new RecaptchaConfig(secretV2: 'test-secret');
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return $this->currentResponse;
            },
        );
        $this->client = new RecaptchaClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);
    }

    #[Test]
    public function verifyReturnsSuccess(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $result = $this->client->verify(token: 'valid-token');

        $this->assertTrue($result->success);
        $this->assertSame([], $result->errorCodes);
    }

    #[Test]
    public function verifyReturnsFailureWithCodes(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->client->verify(token: 'bad-token');

        $this->assertFalse($result->success);
        $this->assertSame(['invalid-input-response'], $result->errorCodes);
    }

    #[Test]
    public function verifyReturnsScore(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"login"}');

        $result = $this->client->verify(token: 'token');

        $this->assertSame(0.9, $result->score);
        $this->assertSame('login', $result->action);
    }

    #[Test]
    public function verifySendsRemoteIp(): void
    {
        $config = new RecaptchaConfig(secretV2: 'test-secret', sendRemoteIp: true);
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return new Response(200, [], '{"success":true}');
            },
        );
        $client = new RecaptchaClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);

        $client->verify(token: 'token', clientIp: '1.2.3.4');

        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('remoteip=1.2.3.4', $body);
    }

    #[Test]
    public function verifyWithSecretUsesCustomSecret(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verifyWithSecret(token: 'token', secret: 'custom-secret');

        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('secret=custom-secret', $body);
    }

    #[Test]
    public function verifyV3UsesSecretV3(): void
    {
        $config = new RecaptchaConfig(secretV2: 'v2-secret', secretV3: 'v3-secret');
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return new Response(200, [], '{"success":true}');
            },
        );
        $client = new RecaptchaClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);

        $client->verifyV3(token: 'token');

        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringContainsString('secret=v3-secret', $body);
    }

    #[Test]
    public function verifyWithoutClientIpOmitsRemoteIp(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verify(token: 'token');

        $this->assertNotNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        $this->assertStringNotContainsString('remoteip', $body);
    }

    #[Test]
    public function verifyWithSendRemoteIpButNoIpOmitsRemoteIp(): void
    {
        $config = new RecaptchaConfig(secretV2: 'test-secret', sendRemoteIp: true);
        $psr17 = new Psr17Factory();
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return new Response(200, [], '{"success":true}');
            },
        );
        $client = new RecaptchaClient(config: $config, httpClient: $httpClient, requestFactory: $psr17, streamFactory: $psr17);

        $client->verify(token: 'token');

        $this->assertNotNull($this->lastRequest);
        $this->assertStringNotContainsString('remoteip', $this->lastRequest->getBody()->__toString());
    }

    #[Test]
    public function verifyParsesDeeplyNestedJson(): void
    {
        $deepArray = array_fill(0, 100, 'x');
        $deepJson = json_encode($deepArray, JSON_THROW_ON_ERROR);
        $payload = '{"success":true,"deep":' . $deepJson . '}';

        $this->currentResponse = new Response(200, [], $payload);

        $result = $this->client->verify(token: 'token');

        $this->assertTrue($result->success);
    }
}
