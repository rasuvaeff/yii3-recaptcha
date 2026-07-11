<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

use Rasuvaeff\Yii3Recaptcha\Exception\MissingClientException;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Shared plumbing for the v2/v3 rule handlers: the verification client, the
 * client-IP resolver and message translation. The concrete handlers implement
 * only the version-specific verdict logic.
 *
 * Dependencies are optional so the handlers work both ways:
 *  - constructed by a container-backed rule-handler resolver → deps injected;
 *  - constructed with no args by the default `SimpleRuleHandlerContainer` →
 *    deps fall back to {@see RecaptchaRegistry} (populated at bootstrap).
 *
 * @internal
 */
abstract readonly class AbstractRecaptchaRuleHandler
{
    public function __construct(
        private ?RecaptchaClient $client = null,
        private ?ClientIpResolverInterface $ipResolver = null,
        private ?TranslatorInterface $translator = null,
        private string $translationCategory = 'yii3-recaptcha',
    ) {}

    final protected function client(): RecaptchaClient
    {
        return $this->client
            ?? RecaptchaRegistry::client()
            ?? throw new MissingClientException();
    }

    final protected function resolveClientIp(bool $send): ?string
    {
        if (!$send) {
            return null;
        }

        $resolver = $this->ipResolver
            ?? RecaptchaRegistry::ipResolver()
            ?? new RemoteAddrClientIpResolver();

        return $resolver->resolve();
    }

    final protected function translate(string $message): string
    {
        $translator = $this->translator ?? RecaptchaRegistry::translator();

        return $translator?->translate($message, [], $this->translationCategory) ?? $message;
    }
}
