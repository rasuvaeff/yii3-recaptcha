<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

use Yiisoft\RequestProvider\RequestNotSetException;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Exception\UnexpectedRuleException;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RuleHandlerInterface;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;

/**
 * @api
 */
final readonly class RecaptchaV3RuleHandler implements RuleHandlerInterface
{
    public function __construct(
        private ?RecaptchaClient $client = null,
        private ?RequestProviderInterface $requestProvider = null,
        private ?TranslatorInterface $translator = null,
        private string $translationCategory = 'yii3-recaptcha',
    ) {}

    private function client(): RecaptchaClient
    {
        return $this->client
            ?? RecaptchaRegistry::client()
            ?? throw new \RuntimeException('RecaptchaClient is not available. Ensure rasuvaeff/yii3-recaptcha bootstrap is registered.');
    }

    #[\Override]
    public function validate(mixed $value, RuleInterface $rule, ValidationContext $context): Result
    {
        if (!$rule instanceof RecaptchaV3Rule) {
            throw new UnexpectedRuleException(RecaptchaV3Rule::class, $rule);
        }

        $result = new Result();

        if (!\is_string($value) || $value === '') {
            return $result->addError(
                $this->translate($rule->getMessage()),
                [
                    'property' => $context->getTranslatedProperty(),
                ],
            );
        }

        $clientIp = $rule->getSendRemoteIp()
            ? $this->resolveClientIp()
            : null;

        $secret = $rule->getSecret();
        $verificationResult = $secret !== null
            ? $this->client()->verifyWithSecret(token: $value, secret: $secret, clientIp: $clientIp)
            : $this->client()->verifyV3(token: $value, clientIp: $clientIp);

        if (!$verificationResult->success) {
            return $result->addError(
                $this->translate($rule->getMessage()),
                [
                    'property' => $context->getTranslatedProperty(),
                    'errorCodes' => implode(', ', $verificationResult->errorCodes),
                ],
            );
        }

        if ($verificationResult->score === null || $verificationResult->score < $rule->getThreshold()) {
            return $result->addError(
                $this->translate($rule->getScoreTooLowMessage()),
                [
                    'property' => $context->getTranslatedProperty(),
                    'score' => (string) ($verificationResult->score ?? 0.0),
                    'threshold' => (string) $rule->getThreshold(),
                ],
            );
        }

        $expectedAction = $rule->getAction();
        if ($expectedAction !== null && $verificationResult->action !== $expectedAction) {
            return $result->addError(
                $this->translate($rule->getActionMismatchMessage()),
                [
                    'property' => $context->getTranslatedProperty(),
                    'expected' => $expectedAction,
                    'actual' => $verificationResult->action ?? '',
                ],
            );
        }

        return $result;
    }

    private function translate(string $message): string
    {
        $translator = $this->translator ?? RecaptchaRegistry::translator();
        if ($translator === null) {
            return $message;
        }

        return $translator->translate(
            $message,
            [],
            $this->translationCategory,
        );
    }

    private function resolveClientIp(): ?string
    {
        $provider = $this->requestProvider ?? RecaptchaRegistry::requestProvider();
        if ($provider === null) {
            return null;
        }

        try {
            $serverParams = $provider->get()->getServerParams();
        } catch (RequestNotSetException) {
            return null;
        }

        if (!isset($serverParams['REMOTE_ADDR']) || !\is_string($serverParams['REMOTE_ADDR'])) {
            return null;
        }

        return $serverParams['REMOTE_ADDR'];
    }
}
