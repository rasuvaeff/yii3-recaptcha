<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

/**
 * @api
 */
enum RecaptchaV2Size: string
{
    case Normal = 'normal';
    case Compact = 'compact';
    case Invisible = 'invisible';
}
