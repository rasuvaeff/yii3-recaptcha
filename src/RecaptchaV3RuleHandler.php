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
final readonly class RecaptchaV3RuleHandler extends AbstractRecaptchaRuleHandler implements RuleHandlerInterface
{
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

        $clientIp = $this->resolveClientIp($rule->getSendRemoteIp());

        $secret = $rule->getSecret();
        $verificationResult = $secret !== null
            ? $this->client()->verifyWithSecret(token: $value, secret: $secret, clientIp: $clientIp)
            : $this->client()->verifyV3(token: $value, clientIp: $clientIp);

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
}
