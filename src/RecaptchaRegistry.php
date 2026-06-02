<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Static registry populated by RecaptchaBootstrap during application bootstrap.
 * Allows rule handlers to work with SimpleRuleHandlerContainer (no-arg construction).
 *
 * @internal
 */
final class RecaptchaRegistry
{
    private static ?RecaptchaClient $client = null;
    private static ?RequestProviderInterface $requestProvider = null;
    private static ?TranslatorInterface $translator = null;
    private static string $translationCategory = 'yii3-recaptcha';

    public static function configure(
        RecaptchaClient $client,
        ?RequestProviderInterface $requestProvider = null,
        ?TranslatorInterface $translator = null,
        string $translationCategory = 'yii3-recaptcha',
    ): void {
        self::$client = $client;
        self::$requestProvider = $requestProvider;
        self::$translator = $translator;
        self::$translationCategory = $translationCategory;
    }

    public static function client(): ?RecaptchaClient
    {
        return self::$client;
    }

    public static function requestProvider(): ?RequestProviderInterface
    {
        return self::$requestProvider;
    }

    public static function translator(): ?TranslatorInterface
    {
        return self::$translator;
    }

    public static function translationCategory(): string
    {
        return self::$translationCategory;
    }
}
