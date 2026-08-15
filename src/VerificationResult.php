<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

/**
 * @api
 */
final readonly class VerificationResult
{
    /**
     * Synthetic error code used when the siteverify endpoint could not be
     * reached or returned an unusable response (transport/HTTP/JSON failure),
     * as opposed to a genuine `success:false` verdict from Google.
     */
    public const string TRANSPORT_ERROR = 'transport-error';

    /**
     * @param string[] $errorCodes
     */
    public function __construct(
        public bool $success,
        public array $errorCodes = [],
        public ?float $score = null,
        public ?string $action = null,
        public ?string $hostname = null,
        public ?string $challengeTs = null,
    ) {}

    /**
     * Failed result representing an unreachable/unusable siteverify endpoint.
     */
    public static function transportError(): self
    {
        return new self(
            success: false,
            errorCodes: [self::TRANSPORT_ERROR],
        );
    }

    /**
     * True when the failure is a transport/HTTP/JSON error rather than a
     * verification verdict — lets callers fail open on an outage if they choose.
     */
    public function isTransportError(): bool
    {
        return \in_array(self::TRANSPORT_ERROR, $this->errorCodes, strict: true);
    }
}
