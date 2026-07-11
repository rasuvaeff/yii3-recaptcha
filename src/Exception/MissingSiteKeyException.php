<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Exception;

use RuntimeException;

/**
 * Thrown when a widget or field is rendered without a site key — neither set
 * explicitly nor available from the injected configuration.
 *
 * @api
 */
final class MissingSiteKeyException extends RuntimeException implements RecaptchaException
{
    public function __construct(string $message = 'A reCAPTCHA site key is required but was not configured')
    {
        parent::__construct($message);
    }
}
