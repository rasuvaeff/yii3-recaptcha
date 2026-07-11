<?php

declare(strict_types=1);

use Rasuvaeff\Yii3Recaptcha\ClientIpResolverInterface;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2RuleHandler;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3RuleHandler;
use Rasuvaeff\Yii3Recaptcha\RemoteAddrClientIpResolver;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IdMessageReader;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\SimpleMessageFormatter;

/** @var array $params */

return [
    RecaptchaConfig::class => [
        '__construct()' => [
            'siteKeyV2' => $params['rasuvaeff/yii3-recaptcha']['siteKeyV2'],
            'secretV2' => $params['rasuvaeff/yii3-recaptcha']['secretV2'],
            'siteKeyV3' => $params['rasuvaeff/yii3-recaptcha']['siteKeyV3'],
            'secretV3' => $params['rasuvaeff/yii3-recaptcha']['secretV3'],
            'verifyUrl' => $params['rasuvaeff/yii3-recaptcha']['verifyUrl'],
            'sendRemoteIp' => $params['rasuvaeff/yii3-recaptcha']['sendRemoteIp'],
        ],
    ],

    // Default client-IP resolver. Applications behind a proxy/CDN rebind this
    // interface to an adapter over their own client-IP detector.
    ClientIpResolverInterface::class => RemoteAddrClientIpResolver::class,

    // Rule handlers are constructed by the validator's container-backed
    // resolver; deps (client, IP resolver, translator) are autowired here.
    RecaptchaV2RuleHandler::class => [
        '__construct()' => [
            'translationCategory' => $params['rasuvaeff/yii3-recaptcha']['translation.category'],
        ],
    ],
    RecaptchaV3RuleHandler::class => [
        '__construct()' => [
            'translationCategory' => $params['rasuvaeff/yii3-recaptcha']['translation.category'],
        ],
    ],
    'yii3-recaptcha.categorySource' => [
        'definition' => static function () use ($params): CategorySource {
            $reader = class_exists(MessageSource::class)
                ? new MessageSource(dirname(__DIR__) . '/messages')
                : new IdMessageReader();

            $formatter = extension_loaded('intl')
                ? new IntlMessageFormatter()
                : new SimpleMessageFormatter();

            return new CategorySource(
                $params['rasuvaeff/yii3-recaptcha']['translation.category'],
                $reader,
                $formatter,
            );
        },
        'tags' => ['translation.categorySource'],
    ],
];
