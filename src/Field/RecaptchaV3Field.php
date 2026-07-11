<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Field;

use Rasuvaeff\Yii3Recaptcha\RecaptchaV3;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Badge;
use Yiisoft\Form\Field\Base\InputField;
use Yiisoft\FormModel\FormModelInputData;
use Yiisoft\FormModel\FormModelInterface;

/**
 * form-model field for reCAPTCHA v3. Binds the token to a form model property
 * (hidden input named after the property) and delegates rendering to
 * {@see RecaptchaV3}, so there is one rendering path.
 *
 * @api
 */
final class RecaptchaV3Field extends InputField
{
    private ?string $siteKey = null;
    private ?string $action = null;
    private ?string $formId = null;
    private ?RecaptchaV3Badge $badge = null;
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

    public function action(string $action): self
    {
        $new = clone $this;
        $new->action = $action;

        return $new;
    }

    public function formId(string $formId): self
    {
        $new = clone $this;
        $new->formId = $formId;

        return $new;
    }

    public function badge(RecaptchaV3Badge $badge): self
    {
        $new = clone $this;
        $new->badge = $badge;

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
        $widget = (new RecaptchaV3())->withFieldName($this->getInputData()->getName() ?? '');

        if ($this->siteKey !== null) {
            $widget = $widget->withSiteKey($this->siteKey);
        }
        if ($this->action !== null) {
            $widget = $widget->withAction($this->action);
        }
        if ($this->formId !== null) {
            $widget = $widget->withFormId($this->formId);
        }
        if ($this->badge !== null) {
            $widget = $widget->withBadge($this->badge);
        }
        if ($this->nonce !== null) {
            $widget = $widget->withNonce($this->nonce);
        }

        return $widget->render();
    }
}
