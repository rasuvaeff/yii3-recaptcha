<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(RecaptchaConfig::class)]
final class RecaptchaConfigTest
{
    public function createsWithDefaults(): void
    {
        $config = new RecaptchaConfig();

        Assert::same($config->siteKeyV2, '');
        Assert::same($config->secretV2, '');
        Assert::same($config->siteKeyV3, '');
        Assert::same($config->secretV3, '');
        Assert::same($config->verifyUrl, 'https://www.google.com/recaptcha/api/siteverify');
        Assert::false($config->sendRemoteIp);
    }

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

        Assert::same($config->siteKeyV2, 'v2-key');
        Assert::same($config->secretV2, 'v2-secret');
        Assert::same($config->siteKeyV3, 'v3-key');
        Assert::same($config->secretV3, 'v3-secret');
        Assert::same($config->verifyUrl, 'https://custom.example.com/verify');
        Assert::true($config->sendRemoteIp);
    }
}
