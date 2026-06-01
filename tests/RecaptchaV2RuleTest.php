<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2RuleHandler;

#[CoversClass(RecaptchaV2Rule::class)]
final class RecaptchaV2RuleTest extends TestCase
{
    #[Test]
    public function usesDefaults(): void
    {
        $rule = new RecaptchaV2Rule();

        $this->assertSame('The CAPTCHA verification failed.', $rule->getMessage());
        $this->assertNull($rule->getSecret());
        $this->assertFalse($rule->getSendRemoteIp());
        $this->assertNull($rule->getSkipOnEmpty());
        $this->assertFalse($rule->shouldSkipOnError());
        $this->assertNull($rule->getWhen());
    }

    #[Test]
    public function storesAllValues(): void
    {
        $when = static fn(): bool => true;

        $rule = new RecaptchaV2Rule(
            message: 'Prove you are human',
            secret: 'override-secret',
            sendRemoteIp: true,
            skipOnEmpty: true,
            skipOnError: true,
            when: $when,
        );

        $this->assertSame('Prove you are human', $rule->getMessage());
        $this->assertSame('override-secret', $rule->getSecret());
        $this->assertTrue($rule->getSendRemoteIp());
        $this->assertTrue($rule->getSkipOnEmpty());
        $this->assertTrue($rule->shouldSkipOnError());
        $this->assertSame($when, $rule->getWhen());
    }

    #[Test]
    public function pointsToItsHandler(): void
    {
        $rule = new RecaptchaV2Rule();

        $this->assertSame(RecaptchaV2RuleHandler::class, $rule->getHandler());
    }
}
