<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
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

        // Fail closed on transport: an unreachable/erroring endpoint yields a
        // failed VerificationResult tagged TRANSPORT_ERROR, never an exception,
        // so the validator pipeline stays predictable. Callers who prefer to
        // fail open on an outage use VerificationResult::isTransportError().
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface) {
            return VerificationResult::transportError();
        }

        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            return VerificationResult::transportError();
        }

        try {
            /** @var mixed $data */
            $data = json_decode(
                json: $response->getBody()->__toString(),
                associative: true,
                depth: 512,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            return VerificationResult::transportError();
        }

        if (!\is_array($data)) {
            return VerificationResult::transportError();
        }

        /** @var array{success?: mixed, error-codes?: mixed, score?: mixed, action?: mixed, hostname?: mixed, challenge_ts?: mixed} $data */
        return new VerificationResult(
            success: (bool) ($data['success'] ?? false),
            errorCodes: $this->normalizeErrorCodes($data['error-codes'] ?? []),
            score: isset($data['score']) ? (float) $data['score'] : null,
            action: isset($data['action']) ? (string) $data['action'] : null,
            hostname: isset($data['hostname']) ? (string) $data['hostname'] : null,
            challengeTs: isset($data['challenge_ts']) ? (string) $data['challenge_ts'] : null,
        );
    }

    /**
     * @return list<string>
     */
    private function normalizeErrorCodes(mixed $codes): array
    {
        if (!\is_array($codes)) {
            return [];
        }

        return array_values(array_filter($codes, 'is_string'));
    }
}
