<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Recaptcha\VerificationResult;

#[CoversClass(VerificationResult::class)]
final class VerificationResultTest extends TestCase
{
    #[Test]
    public function createsWithDefaults(): void
    {
        $result = new VerificationResult(success: true);

        $this->assertTrue($result->success);
        $this->assertSame([], $result->errorCodes);
        $this->assertNull($result->score);
        $this->assertNull($result->action);
        $this->assertNull($result->hostname);
        $this->assertNull($result->challengeTs);
    }

    #[Test]
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

        $this->assertFalse($result->success);
        $this->assertSame(['invalid-input-response'], $result->errorCodes);
        $this->assertSame(0.7, $result->score);
        $this->assertSame('login', $result->action);
        $this->assertSame('example.com', $result->hostname);
        $this->assertSame('2024-01-01T00:00:00Z', $result->challengeTs);
    }
}
