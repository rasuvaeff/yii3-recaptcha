<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

/**
 * @api
 */
final readonly class RecaptchaConfig
{
    public function __construct(
        public string $siteKeyV2 = '',
        public string $secretV2 = '',
        public string $siteKeyV3 = '',
        public string $secretV3 = '',
        public string $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify',
        public bool $sendRemoteIp = false,
    ) {}
}
