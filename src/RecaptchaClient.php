<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @api
 */
final readonly class RecaptchaClient
{
    public function __construct(
        private RecaptchaConfig $config,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function verify(string $token, ?string $clientIp = null): VerificationResult
    {
        return $this->doVerify(
            secret: $this->config->secretV2,
            token: $token,
            clientIp: $clientIp,
        );
    }

    public function verifyV3(string $token, ?string $clientIp = null): VerificationResult
    {
        return $this->doVerify(
            secret: $this->config->secretV3,
            token: $token,
            clientIp: $clientIp,
        );
    }

    public function verifyWithSecret(string $token, string $secret, ?string $clientIp = null): VerificationResult
    {
        return $this->doVerify(
            secret: $secret,
            token: $token,
            clientIp: $clientIp,
        );
    }

    private function doVerify(string $secret, string $token, ?string $clientIp): VerificationResult
    {
        $body = http_build_query(
            data: array_filter([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $this->config->sendRemoteIp ? $clientIp : null,
            ]),
        );

        $request = $this->requestFactory
            ->createRequest('POST', $this->config->verifyUrl)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streamFactory->createStream($body));

        $response = $this->httpClient->sendRequest($request);

        /** @var array{success: bool, error-codes?: string[], score?: float, action?: string, hostname?: string, challenge_ts?: string} $data */
        $data = json_decode(
            json: $response->getBody()->__toString(),
            associative: true,
            depth: 512,
            flags: JSON_THROW_ON_ERROR,
        );

        return new VerificationResult(
            success: $data['success'],
            errorCodes: $data['error-codes'] ?? [],
            score: $data['score'] ?? null,
            action: $data['action'] ?? null,
            hostname: $data['hostname'] ?? null,
            challengeTs: $data['challenge_ts'] ?? null,
        );
    }
}
