<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Rasuvaeff\Yii3Recaptcha\VerificationResult;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(VerificationResult::class)]
final class VerificationResultTest
{
    public function createsWithDefaults(): void
    {
        $result = new VerificationResult(success: true);

        Assert::true($result->success);
        Assert::same($result->errorCodes, []);
        Assert::null($result->score);
        Assert::null($result->action);
        Assert::null($result->hostname);
        Assert::null($result->challengeTs);
    }

    public function createsWithAllFields(): void
    {
        $result = new VerificationResult(
            success: false,
            errorCodes: ['invalid-input-response'],
            score: 0.7,
            action: 'login',
            hostname: 'example.com',
            challengeTs: '2024-01-01T00:00:00Z',
        );

        Assert::false($result->success);
        Assert::same($result->errorCodes, ['invalid-input-response']);
        Assert::same($result->score, 0.7);
        Assert::same($result->action, 'login');
        Assert::same($result->hostname, 'example.com');
        Assert::same($result->challengeTs, '2024-01-01T00:00:00Z');
    }

    public function transportErrorIsFailedAndFlagged(): void
    {
        $result = VerificationResult::transportError();

        Assert::false($result->success);
        Assert::same($result->errorCodes, [VerificationResult::TRANSPORT_ERROR]);
        Assert::true($result->isTransportError());
    }

    public function verdictFailureIsNotTransportError(): void
    {
        $result = new VerificationResult(success: false, errorCodes: ['invalid-input-response']);

        Assert::false($result->isTransportError());
    }
}
