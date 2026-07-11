<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Field;

use Rasuvaeff\Yii3Recaptcha\RecaptchaV2;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Size;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Theme;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Type;
use Yiisoft\Form\Field\Base\InputField;
use Yiisoft\FormModel\FormModelInputData;
use Yiisoft\FormModel\FormModelInterface;

/**
 * form-model field for the reCAPTCHA v2 checkbox. Binds the verification token
 * to a form model property (via a hidden input named after the property) and
 * delegates rendering to {@see RecaptchaV2}, so there is one rendering path.
 *
 * @api
 */
final class RecaptchaV2Field extends InputField
{
    private ?string $siteKey = null;
    private ?RecaptchaV2Theme $theme = null;
    private ?RecaptchaV2Type $type = null;
    private ?RecaptchaV2Size $size = null;
    private ?string $nonce = null;

    public static function field(FormModelInterface $formModel, string $property): self
    {
        return (new self())->inputData(new FormModelInputData($formModel, $property));
    }

    public function siteKey(string $siteKey): self
    {
        $new = clone $this;
        $new->siteKey = $siteKey;

        return $new;
    }

    public function theme(RecaptchaV2Theme $theme): self
    {
        $new = clone $this;
        $new->theme = $theme;

        return $new;
    }

    public function type(RecaptchaV2Type $type): self
    {
        $new = clone $this;
        $new->type = $type;

        return $new;
    }

    public function size(RecaptchaV2Size $size): self
    {
        $new = clone $this;
        $new->size = $size;

        return $new;
    }

    public function nonce(string $nonce): self
    {
        $new = clone $this;
        $new->nonce = $nonce;

        return $new;
    }

    #[\Override]
    protected function generateInput(): string
    {
        // A form field always binds the token to its model property. getName()
        // is non-null via FormModelInputData; `?? ''` only satisfies the
        // nullable InputDataInterface signature for static analysis.
        $widget = (new RecaptchaV2())->withResponseFieldName($this->getInputData()->getName() ?? '');

        if ($this->siteKey !== null) {
            $widget = $widget->withSiteKey($this->siteKey);
        }
        if ($this->theme !== null) {
            $widget = $widget->withTheme($this->theme);
        }
        if ($this->type !== null) {
            $widget = $widget->withType($this->type);
        }
        if ($this->size !== null) {
            $widget = $widget->withSize($this->size);
        }
        if ($this->nonce !== null) {
            $widget = $widget->withNonce($this->nonce);
        }

        return $widget->render();
    }
}
