<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Exception;

use RuntimeException;

/**
 * Thrown when a rule handler has no `RecaptchaClient` — neither injected nor
 * available from {@see \Rasuvaeff\Yii3Recaptcha\RecaptchaRegistry}. Register the
 * package config (config-plugin bootstrap) or bind a container-backed
 * rule-handler resolver.
 *
 * @api
 */
final class MissingClientException extends RuntimeException implements RecaptchaException
{
    public function __construct(
        string $message = 'RecaptchaClient is not available. Register the package config (bootstrap) or a container-backed rule-handler resolver.',
    ) {
        parent::__construct($message);
    }
}
