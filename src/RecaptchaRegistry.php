<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

use Yiisoft\Translator\TranslatorInterface;

/**
 * Static fallback populated during application bootstrap. Lets the rule
 * handlers resolve their dependencies even when the validator constructs them
 * with no arguments (the default `SimpleRuleHandlerContainer`). When the
 * handler is built by a container-backed resolver the injected deps win and
 * this registry is never consulted.
 *
 * @api
 */
final class RecaptchaRegistry
{
    private static ?RecaptchaClient $client = null;
    private static ?ClientIpResolverInterface $ipResolver = null;
    private static ?TranslatorInterface $translator = null;

    public static function configure(
        RecaptchaClient $client,
        ?ClientIpResolverInterface $ipResolver = null,
        ?TranslatorInterface $translator = null,
    ): void {
        self::$client = $client;
        self::$ipResolver = $ipResolver;
        self::$translator = $translator;
    }

    public static function client(): ?RecaptchaClient
    {
        return self::$client;
    }

    public static function ipResolver(): ?ClientIpResolverInterface
    {
        return self::$ipResolver;
    }

    public static function translator(): ?TranslatorInterface
    {
        return self::$translator;
    }
}
