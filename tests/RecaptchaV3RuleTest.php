<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use InvalidArgumentException;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Rule;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3RuleHandler;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(RecaptchaV3Rule::class)]
final class RecaptchaV3RuleTest
{
    public function usesDefaults(): void
    {
        $rule = new RecaptchaV3Rule();

        Assert::same($rule->getMessage(), 'The CAPTCHA verification failed.');
        Assert::same($rule->getScoreTooLowMessage(), 'The CAPTCHA score is too low.');
        Assert::same($rule->getActionMismatchMessage(), 'The CAPTCHA action does not match.');
        Assert::null($rule->getSecret());
        Assert::same($rule->getThreshold(), 0.5);
        Assert::null($rule->getAction());
        Assert::false($rule->getSendRemoteIp());
        Assert::null($rule->getSkipOnEmpty());
        Assert::false($rule->shouldSkipOnError());
        Assert::null($rule->getWhen());
    }

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

        Assert::same($rule->getMessage(), 'Prove you are human');
        Assert::same($rule->getScoreTooLowMessage(), 'Score too low');
        Assert::same($rule->getActionMismatchMessage(), 'Action mismatch');
        Assert::same($rule->getSecret(), 'override-secret');
        Assert::same($rule->getThreshold(), 0.7);
        Assert::same($rule->getAction(), 'login');
        Assert::true($rule->getSendRemoteIp());
        Assert::true($rule->getSkipOnEmpty());
        Assert::true($rule->shouldSkipOnError());
        Assert::same($rule->getWhen(), $when);
    }

    public function pointsToItsHandler(): void
    {
        $rule = new RecaptchaV3Rule();

        Assert::same($rule->getHandler(), RecaptchaV3RuleHandler::class);
    }

    #[DataProvider('invalidThresholdProvider')]
    public function throwsOnInvalidThreshold(float $threshold): void
    {
        try {
            new RecaptchaV3Rule(threshold: $threshold);
            Assert::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            Assert::string($e->getMessage())->contains('Threshold must be between 0.0 and 1.0');
        }
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function invalidThresholdProvider(): iterable
    {
        yield 'below zero' => [-0.1];
        yield 'above one' => [1.1];
    }

    public function thresholdZeroIsValid(): void
    {
        $rule = new RecaptchaV3Rule(threshold: 0.0);

        Assert::same($rule->getThreshold(), 0.0);
    }

    public function thresholdOneIsValid(): void
    {
        $rule = new RecaptchaV3Rule(threshold: 1.0);

        Assert::same($rule->getThreshold(), 1.0);
    }
}
