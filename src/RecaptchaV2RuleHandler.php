<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

use Yiisoft\Validator\Exception\UnexpectedRuleException;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RuleHandlerInterface;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;

/**
 * @api
 */
final readonly class RecaptchaV2RuleHandler extends AbstractRecaptchaRuleHandler implements RuleHandlerInterface
{
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

        $clientIp = $this->resolveClientIp($rule->getSendRemoteIp());

        $secret = $rule->getSecret();
        $verificationResult = $secret !== null
            ? $this->client()->verifyWithSecret(token: $value, secret: $secret, clientIp: $clientIp)
            : $this->client()->verify(token: $value, clientIp: $clientIp);

        if (!$verificationResult->success) {
            if ($verificationResult->isTransportError() && $rule->isFailOpenOnError()) {
                return $result;
            }

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
}
