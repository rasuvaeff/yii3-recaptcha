<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Rasuvaeff\Yii3Recaptcha\ClientIpResolverInterface;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaRegistry;
use Yiisoft\Translator\TranslatorInterface;

return [
    static function (ContainerInterface $container): void {
        RecaptchaRegistry::configure(
            client: $container->get(RecaptchaClient::class),
            ipResolver: $container->has(ClientIpResolverInterface::class)
                ? $container->get(ClientIpResolverInterface::class)
                : null,
            translator: $container->has(TranslatorInterface::class)
                ? $container->get(TranslatorInterface::class)
                : null,
        );
    },
];
