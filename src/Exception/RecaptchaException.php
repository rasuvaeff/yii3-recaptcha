<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Exception;

/**
 * Marker implemented by every exception this package throws, so consumers can
 * catch all of them with a single `catch (RecaptchaException $e)`.
 *
 * @api
 */
interface RecaptchaException extends \Throwable {}
