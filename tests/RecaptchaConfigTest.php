<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;

#[CoversClass(RecaptchaConfig::class)]
final class RecaptchaConfigTest extends TestCase
{
    #[Test]
    public function createsWithDefaults(): void
    {
        $config = new RecaptchaConfig();

        $this->assertSame('', $config->siteKeyV2);
        $this->assertSame('', $config->secretV2);
        $this->assertSame('', $config->siteKeyV3);
        $this->assertSame('', $config->secretV3);
        $this->assertSame('https://www.google.com/recaptcha/api/siteverify', $config->verifyUrl);
        $this->assertFalse($config->sendRemoteIp);
    }

    #[Test]
    public function createsWithCustomValues(): void
    {
        $config = new RecaptchaConfig(
            siteKeyV2: 'v2-key',
            secretV2: 'v2-secret',
            siteKeyV3: 'v3-key',
            secretV3: 'v3-secret',
            verifyUrl: 'https://custom.example.com/verify',
            sendRemoteIp: true,
        );

        $this->assertSame('v2-key', $config->siteKeyV2);
        $this->assertSame('v2-secret', $config->secretV2);
        $this->assertSame('v3-key', $config->siteKeyV3);
        $this->assertSame('v3-secret', $config->secretV3);
        $this->assertSame('https://custom.example.com/verify', $config->verifyUrl);
        $this->assertTrue($config->sendRemoteIp);
    }
}
