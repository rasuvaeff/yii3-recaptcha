<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2RuleHandler;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(RecaptchaV2Rule::class)]
final class RecaptchaV2RuleTest
{
    public function usesDefaults(): void
    {
        $rule = new RecaptchaV2Rule();

        Assert::same($rule->getMessage(), 'The CAPTCHA verification failed.');
        Assert::null($rule->getSecret());
        Assert::false($rule->getSendRemoteIp());
        Assert::null($rule->getSkipOnEmpty());
        Assert::false($rule->shouldSkipOnError());
        Assert::null($rule->getWhen());
    }

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

        Assert::same($rule->getMessage(), 'Prove you are human');
        Assert::same($rule->getSecret(), 'override-secret');
        Assert::true($rule->getSendRemoteIp());
        Assert::true($rule->getSkipOnEmpty());
        Assert::true($rule->shouldSkipOnError());
        Assert::same($rule->getWhen(), $when);
    }

    public function pointsToItsHandler(): void
    {
        $rule = new RecaptchaV2Rule();

        Assert::same($rule->getHandler(), RecaptchaV2RuleHandler::class);
    }
}
