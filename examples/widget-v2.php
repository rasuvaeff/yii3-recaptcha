<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Size;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Theme;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Type;

$config = new RecaptchaConfig(siteKeyV2: '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI', secretV2: 'test');

echo "=== Default v2 widget ===\n";
echo (new RecaptchaV2(config: $config))->render();
echo "\n\n";

echo "=== Dark theme, compact ===\n";
echo (new RecaptchaV2(config: $config))
    ->withTheme(RecaptchaV2Theme::Dark)
    ->withSize(RecaptchaV2Size::Compact)
    ->render();
echo "\n\n";

echo "=== Audio type, invisible size ===\n";
echo (new RecaptchaV2(config: $config))
    ->withType(RecaptchaV2Type::Audio)
    ->withSize(RecaptchaV2Size::Invisible)
    ->render();
echo "\n";
