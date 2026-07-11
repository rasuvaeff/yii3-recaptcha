<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests\Field;

use Rasuvaeff\Yii3Recaptcha\Field\RecaptchaV3Field;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Badge;
use Rasuvaeff\Yii3Recaptcha\Tests\Support\CaptchaForm;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(RecaptchaV3Field::class)]
final class RecaptchaV3FieldTest
{
    public function bindsTokenToFormModelProperty(): void
    {
        $html = RecaptchaV3Field::field(new CaptchaForm(), 'token')
            ->siteKey('site-key')
            ->render();

        Assert::string($html)->contains('token');
        Assert::string($html)->contains('grecaptcha.execute("site-key"');
    }

    public function forwardsActionAndBadgeToWidget(): void
    {
        $html = RecaptchaV3Field::field(new CaptchaForm(), 'token')
            ->siteKey('site-key')
            ->action('login')
            ->badge(RecaptchaV3Badge::Hidden)
            ->render();

        Assert::string($html)->contains('{action: "login"}');
        Assert::string($html)->contains('grecaptcha-badge');
    }

    public function appliesCspNonce(): void
    {
        $html = RecaptchaV3Field::field(new CaptchaForm(), 'token')
            ->siteKey('site-key')
            ->nonce('abc123')
            ->render();

        Assert::string($html)->contains('nonce="abc123"');
    }

    public function withMethodsReturnNewImmutableInstances(): void
    {
        $field = RecaptchaV3Field::field(new CaptchaForm(), 'token');

        Assert::notSame($field, $field->siteKey('k'));
        Assert::notSame($field, $field->action('login'));
        Assert::notSame($field, $field->formId('f'));
        Assert::notSame($field, $field->badge(RecaptchaV3Badge::Hidden));
        Assert::notSame($field, $field->nonce('n'));
    }
}
