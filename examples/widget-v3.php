<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Badge;

$config = new RecaptchaConfig(siteKeyV3: '6LcR_TEMP_TEST_KEY', secretV3: 'test');

echo "=== v3 widget (token filled on load) ===\n";
echo (new RecaptchaV3(config: $config))->render();
echo "\n\n";

echo "=== v3 widget (invisible submit + hidden badge) ===\n";
echo (new RecaptchaV3(config: $config))
    ->withAction('login')
    ->withFieldName('recaptchaToken')
    ->withFormId('login-form')
    ->withBadge(RecaptchaV3Badge::Hidden)
    ->render();
echo "\n";
