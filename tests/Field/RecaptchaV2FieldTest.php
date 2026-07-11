<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests\Field;

use Rasuvaeff\Yii3Recaptcha\Field\RecaptchaV2Field;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Theme;
use Rasuvaeff\Yii3Recaptcha\Tests\Support\CaptchaForm;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(RecaptchaV2Field::class)]
final class RecaptchaV2FieldTest
{
    public function bindsTokenToFormModelProperty(): void
    {
        $html = RecaptchaV2Field::field(new CaptchaForm(), 'token')
            ->siteKey('site-key')
            ->render();

        Assert::string($html)->contains('token');
        Assert::string($html)->contains('"sitekey":"site-key"');
    }

    public function forwardsOptionsToWidget(): void
    {
        $html = RecaptchaV2Field::field(new CaptchaForm(), 'token')
            ->siteKey('site-key')
            ->theme(RecaptchaV2Theme::Dark)
            ->render();

        Assert::string($html)->contains('"theme":"dark"');
    }

    public function appliesCspNonce(): void
    {
        $html = RecaptchaV2Field::field(new CaptchaForm(), 'token')
            ->siteKey('site-key')
            ->nonce('abc123')
            ->render();

        Assert::string($html)->contains('nonce="abc123"');
    }
}
