<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

/**
 * @api
 */
enum RecaptchaV2Type: string
{
    case Image = 'image';
    case Audio = 'audio';
}
