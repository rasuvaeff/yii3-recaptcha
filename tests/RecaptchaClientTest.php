<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(RecaptchaClient::class)]
final class RecaptchaClientTest
{
    private RecaptchaClient $client;

    private ?RequestInterface $lastRequest = null;

    private Response $currentResponse;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->client = $this->createClient(
            config: new RecaptchaConfig(secretV2: 'test-secret'),
            response: new Response(200, [], '{"success":true}'),
        );
    }

    public function verifyReturnsSuccess(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $result = $this->client->verify(token: 'valid-token');

        Assert::true($result->success);
        Assert::same($result->errorCodes, []);
    }

    public function verifyReturnsFailureWithCodes(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":false,"error-codes":["invalid-input-response"]}');

        $result = $this->client->verify(token: 'bad-token');

        Assert::false($result->success);
        Assert::same($result->errorCodes, ['invalid-input-response']);
    }

    public function verifyReturnsScore(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true,"score":0.9,"action":"login"}');

        $result = $this->client->verify(token: 'token');

        Assert::same($result->score, 0.9);
        Assert::same($result->action, 'login');
    }

    public function verifySendsRemoteIp(): void
    {
        $client = $this->createClient(
            config: new RecaptchaConfig(secretV2: 'test-secret', sendRemoteIp: true),
            response: new Response(200, [], '{"success":true}'),
        );

        $client->verify(token: 'token', clientIp: '1.2.3.4');

        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->contains('remoteip=1.2.3.4');
    }

    public function verifyWithSecretUsesCustomSecret(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verifyWithSecret(token: 'token', secret: 'custom-secret');

        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->contains('secret=custom-secret');
    }

    public function verifyV3UsesSecretV3(): void
    {
        $client = $this->createClient(
            config: new RecaptchaConfig(secretV2: 'v2-secret', secretV3: 'v3-secret'),
            response: new Response(200, [], '{"success":true}'),
        );

        $client->verifyV3(token: 'token');

        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->contains('secret=v3-secret');
    }

    public function verifyWithoutClientIpOmitsRemoteIp(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $this->client->verify(token: 'token');

        Assert::notNull($this->lastRequest);
        $body = $this->lastRequest->getBody()->__toString();
        Assert::string($body)->notContains('remoteip');
    }

    public function verifyWithSendRemoteIpButNoIpOmitsRemoteIp(): void
    {
        $client = $this->createClient(
            config: new RecaptchaConfig(secretV2: 'test-secret', sendRemoteIp: true),
            response: new Response(200, [], '{"success":true}'),
        );

        $client->verify(token: 'token');

        Assert::notNull($this->lastRequest);
        Assert::string($this->lastRequest->getBody()->__toString())->notContains('remoteip');
    }

    public function verifyParsesDeeplyNestedJson(): void
    {
        $deepArray = array_fill(0, 100, 'x');
        $deepJson = json_encode($deepArray, JSON_THROW_ON_ERROR);
        $payload = '{"success":true,"deep":' . $deepJson . '}';

        $this->currentResponse = new Response(200, [], $payload);

        $result = $this->client->verify(token: 'token');

        Assert::true($result->success);
    }

    public function verifyParsesJsonAtMaxAllowedDepth(): void
    {
        $inner = 'true';
        for ($i = 0; $i < 510; $i++) {
            $inner = '[' . $inner . ']';
        }
        $json = '{"success":true,"_":' . $inner . '}';

        $this->currentResponse = new Response(200, [], $json);

        $result = $this->client->verify(token: 'token');

        Assert::true($result->success);
    }

    public function verifyReturnsTransportErrorForJsonExceedingDepth512(): void
    {
        $inner = 'true';
        for ($i = 0; $i < 511; $i++) {
            $inner = '[' . $inner . ']';
        }
        $json = '{"success":true,"_":' . $inner . '}';

        $this->currentResponse = new Response(200, [], $json);

        $result = $this->client->verify(token: 'token');

        Assert::false($result->success);
        Assert::true($result->isTransportError());
    }

    public function verifySendRemoteIpWithEmptyClientIpOmitsRemoteIp(): void
    {
        $client = $this->createClient(
            config: new RecaptchaConfig(secretV2: 'test-secret', sendRemoteIp: true),
            response: new Response(200, [], '{"success":true}'),
        );

        $client->verify(token: 'token', clientIp: '');

        Assert::notNull($this->lastRequest);
        Assert::string($this->lastRequest->getBody()->__toString())->notContains('remoteip');
    }

    public function sendRemoteIpFalseOmitsRemoteIpEvenWhenClientIpProvided(): void
    {
        $client = $this->createClient(
            config: new RecaptchaConfig(secretV2: 'test-secret', sendRemoteIp: false),
            response: new Response(200, [], '{"success":true}'),
        );

        $client->verify(token: 'token', clientIp: '9.9.9.9');

        Assert::notNull($this->lastRequest);
        Assert::string($this->lastRequest->getBody()->__toString())->notContains('remoteip');
    }

    public function verifyReturnsTransportErrorOnNon2xxStatus(): void
    {
        $this->currentResponse = new Response(503, [], 'Service Unavailable');

        $result = $this->client->verify(token: 'token');

        Assert::false($result->success);
        Assert::true($result->isTransportError());
    }

    public function verifyReturnsTransportErrorWhenClientThrows(): void
    {
        $psr17 = new Psr17Factory();
        $client = new RecaptchaClient(
            config: new RecaptchaConfig(secretV2: 'test-secret'),
            httpClient: (new FakeHttpClient())->withCallback(
                static fn(): Response => throw new \Rasuvaeff\Yii3Recaptcha\Tests\Support\ThrowingClientException(),
            ),
            requestFactory: $psr17,
            streamFactory: $psr17,
        );

        $result = $client->verify(token: 'token');

        Assert::false($result->success);
        Assert::true($result->isTransportError());
    }

    public function coercesResponseFieldTypesAndDefaultsSuccess(): void
    {
        // score as a string, other fields as ints: the client casts them to
        // their declared types; a missing `success` defaults to false.
        $this->currentResponse = new Response(
            200,
            [],
            '{"score":"0.7","action":123,"hostname":456,"challenge_ts":789}',
        );

        $result = $this->client->verify(token: 'token');

        Assert::false($result->success);
        Assert::same($result->score, 0.7);
        Assert::same($result->action, '123');
        Assert::same($result->hostname, '456');
        Assert::same($result->challengeTs, '789');
    }

    public function absentOptionalFieldsAreNull(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":true}');

        $result = $this->client->verify(token: 'token');

        Assert::null($result->score);
        Assert::null($result->action);
        Assert::null($result->hostname);
        Assert::null($result->challengeTs);
    }

    public function filtersNonStringErrorCodesAndReindexes(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":false,"error-codes":["valid-code",123,"another"]}');

        $result = $this->client->verify(token: 'token');

        Assert::same($result->errorCodes, ['valid-code', 'another']);
    }

    public function nonStringErrorCodesFieldYieldsEmptyList(): void
    {
        $this->currentResponse = new Response(200, [], '{"success":false,"error-codes":"oops"}');

        $result = $this->client->verify(token: 'token');

        Assert::same($result->errorCodes, []);
    }

    public function non2xxWithParseableBodyStillFailsClosed(): void
    {
        // 503 with a "success" body must not be parsed as a pass.
        $this->currentResponse = new Response(503, [], '{"success":true}');

        $result = $this->client->verify(token: 'token');

        Assert::false($result->success);
        Assert::true($result->isTransportError());
    }

    public function status300IsTreatedAsNon2xx(): void
    {
        $this->currentResponse = new Response(300, [], '{"success":true}');

        $result = $this->client->verify(token: 'token');

        Assert::true($result->isTransportError());
    }

    public function nonArrayJsonBodyFailsClosed(): void
    {
        // valid JSON that decodes to a scalar (not an object/array).
        $this->currentResponse = new Response(200, [], 'true');

        $result = $this->client->verify(token: 'token');

        Assert::false($result->success);
        Assert::true($result->isTransportError());
    }

    private function createClient(RecaptchaConfig $config, Response $response): RecaptchaClient
    {
        $psr17 = new Psr17Factory();
        $this->currentResponse = $response;
        $httpClient = (new FakeHttpClient())
            ->withCallback(function (RequestInterface $request): Response {
                $this->lastRequest = $request;

                return $this->currentResponse;
            });

        return new RecaptchaClient(
            config: $config,
            httpClient: $httpClient,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );
    }
}
