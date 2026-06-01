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
final readonly class RecaptchaV2RuleHandler implements RuleHandlerInterface
{
    public function __construct(
        private RecaptchaClient $client,
        private ?RequestProviderInterface $requestProvider = null,
        private ?TranslatorInterface $translator = null,
        private string $translationCategory = 'yii3-recaptcha',
    ) {}

    #[\Override]
    public function validate(mixed $value, RuleInterface $rule, ValidationContext $context): Result
    {
        if (!$rule instanceof RecaptchaV2Rule) {
            throw new UnexpectedRuleException(RecaptchaV2Rule::class, $rule);
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
            ? $this->client->verifyWithSecret(token: $value, secret: $secret, clientIp: $clientIp)
            : $this->client->verify(token: $value, clientIp: $clientIp);

        if (!$verificationResult->success) {
            return $result->addError(
                $this->translate($rule->getMessage()),
                [
                    'property' => $context->getTranslatedProperty(),
                    'errorCodes' => implode(', ', $verificationResult->errorCodes),
                ],
            );
        }

        return $result;
    }

    private function translate(string $message): string
    {
        if ($this->translator === null) {
            return $message;
        }

        return $this->translator->translate(
            $message,
            [],
            $this->translationCategory,
        );
    }

    private function resolveClientIp(): ?string
    {
        if ($this->requestProvider === null) {
            return null;
        }

        try {
            $serverParams = $this->requestProvider->get()->getServerParams();
        } catch (RequestNotSetException) {
            return null;
        }

        if (!isset($serverParams['REMOTE_ADDR']) || !\is_string($serverParams['REMOTE_ADDR'])) {
            return null;
        }

        return $serverParams['REMOTE_ADDR'];
    }
}
