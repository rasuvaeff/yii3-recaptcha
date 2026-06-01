<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

/**
 * @api
 */
enum RecaptchaV3Badge: string
{
    case BottomRight = 'bottomright';
    case BottomLeft = 'bottomleft';
    case Hidden = 'hidden';
}
