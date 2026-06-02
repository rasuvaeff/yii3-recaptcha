<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3RuleHandler;

#[CoversClass(RecaptchaV3Rule::class)]
final class RecaptchaV3RuleTest extends TestCase
{
    #[Test]
    public function usesDefaults(): void
    {
        $rule = new RecaptchaV3Rule();

        $this->assertSame('The CAPTCHA verification failed.', $rule->getMessage());
        $this->assertSame('The CAPTCHA score is too low.', $rule->getScoreTooLowMessage());
        $this->assertSame('The CAPTCHA action does not match.', $rule->getActionMismatchMessage());
        $this->assertNull($rule->getSecret());
        $this->assertSame(0.5, $rule->getThreshold());
        $this->assertNull($rule->getAction());
        $this->assertFalse($rule->getSendRemoteIp());
        $this->assertNull($rule->getSkipOnEmpty());
        $this->assertFalse($rule->shouldSkipOnError());
        $this->assertNull($rule->getWhen());
    }

    #[Test]
    public function storesAllValues(): void
    {
        $when = static fn(): bool => true;

        $rule = new RecaptchaV3Rule(
            message: 'Prove you are human',
            scoreTooLowMessage: 'Score too low',
            actionMismatchMessage: 'Action mismatch',
            secret: 'override-secret',
            threshold: 0.7,
            action: 'login',
            sendRemoteIp: true,
            skipOnEmpty: true,
            skipOnError: true,
            when: $when,
        );

        $this->assertSame('Prove you are human', $rule->getMessage());
        $this->assertSame('Score too low', $rule->getScoreTooLowMessage());
        $this->assertSame('Action mismatch', $rule->getActionMismatchMessage());
        $this->assertSame('override-secret', $rule->getSecret());
        $this->assertSame(0.7, $rule->getThreshold());
        $this->assertSame('login', $rule->getAction());
        $this->assertTrue($rule->getSendRemoteIp());
        $this->assertTrue($rule->getSkipOnEmpty());
        $this->assertTrue($rule->shouldSkipOnError());
        $this->assertSame($when, $rule->getWhen());
    }

    #[Test]
    public function pointsToItsHandler(): void
    {
        $rule = new RecaptchaV3Rule();

        $this->assertSame(RecaptchaV3RuleHandler::class, $rule->getHandler());
    }

    #[DataProvider('invalidThresholdProvider')]
    #[Test]
    public function throwsOnInvalidThreshold(float $threshold): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Threshold must be between 0.0 and 1.0');

        new RecaptchaV3Rule(threshold: $threshold);
    }

    public static function invalidThresholdProvider(): iterable
    {
        yield 'below zero' => [-0.1];
        yield 'above one' => [1.1];
    }

    #[Test]
    public function thresholdZeroIsValid(): void
    {
        $rule = new RecaptchaV3Rule(threshold: 0.0);

        $this->assertSame(0.0, $rule->getThreshold());
    }

    #[Test]
    public function thresholdOneIsValid(): void
    {
        $rule = new RecaptchaV3Rule(threshold: 1.0);

        $this->assertSame(1.0, $rule->getThreshold());
    }
}
