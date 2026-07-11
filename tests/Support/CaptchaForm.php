<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests\Support;

use Yiisoft\FormModel\FormModel;

/**
 * @internal
 */
final class CaptchaForm extends FormModel
{
    public string $token = '';
}
