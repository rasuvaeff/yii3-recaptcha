<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

use Yiisoft\Translator\TranslatorInterface;

/**
 * Shared plumbing for the v2/v3 rule handlers: the verification client, the
 * client-IP resolver and message translation. The concrete handlers implement
 * only the version-specific verdict logic.
 *
 * @internal
 */
abstract readonly class AbstractRecaptchaRuleHandler
{
    public function __construct(
        private RecaptchaClient $client,
        private ClientIpResolverInterface $ipResolver,
        private ?TranslatorInterface $translator = null,
        private string $translationCategory = 'yii3-recaptcha',
    ) {}

    final protected function client(): RecaptchaClient
    {
        return $this->client;
    }

    final protected function resolveClientIp(bool $send): ?string
    {
        return $send ? $this->ipResolver->resolve() : null;
    }

    final protected function translate(string $message): string
    {
        return $this->translator?->translate($message, [], $this->translationCategory) ?? $message;
    }
}
