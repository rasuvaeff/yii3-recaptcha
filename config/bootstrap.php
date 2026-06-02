<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaRegistry;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;

/** @var array $params */

return [
    static function (ContainerInterface $container) use ($params): void {
        RecaptchaRegistry::configure(
            client: $container->get(RecaptchaClient::class),
            requestProvider: $container->has(RequestProviderInterface::class)
                ? $container->get(RequestProviderInterface::class)
                : null,
            translator: $container->has(TranslatorInterface::class)
                ? $container->get(TranslatorInterface::class)
                : null,
            translationCategory: $params['rasuvaeff/yii3-recaptcha']['translation.category'],
        );
    },
];
