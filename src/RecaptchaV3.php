<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

use Yiisoft\Html\Html;
use Yiisoft\Widget\Widget;

/**
 * @api
 */
final class RecaptchaV3 extends Widget
{
    private const string JS_API_URL = 'https://www.google.com/recaptcha/api.js';
    private const string LEGAL_NOTICE = '<p class="recaptcha-v3-notice">This site is protected by reCAPTCHA and the Google '
        . '<a href="https://policies.google.com/privacy">Privacy Policy</a> and '
        . '<a href="https://policies.google.com/terms">Terms of Service</a> apply.</p>';

    private ?string $siteKey = null;
    private string $action = 'submit';
    private string $fieldName = 'g-recaptcha-response';
    private ?string $fieldId = null;
    private ?string $formId = null;
    private RecaptchaV3Badge $badge = RecaptchaV3Badge::BottomRight;
    private string $jsApiUrl = self::JS_API_URL;

    public function __construct(
        ?RecaptchaConfig $config = null,
    ) {
        if ($config !== null && $config->siteKeyV3 !== '') {
            $this->siteKey = $config->siteKeyV3;
        }
    }

    public function withSiteKey(string $siteKey): self
    {
        $new = clone $this;
        $new->siteKey = $siteKey;

        return $new;
    }

    public function withAction(string $action): self
    {
        $new = clone $this;
        $new->action = $action;

        return $new;
    }

    public function withFieldName(string $fieldName): self
    {
        $new = clone $this;
        $new->fieldName = $fieldName;

        return $new;
    }

    public function withFieldId(string $fieldId): self
    {
        $new = clone $this;
        $new->fieldId = $fieldId;

        return $new;
    }

    public function withFormId(string $formId): self
    {
        $new = clone $this;
        $new->formId = $formId;

        return $new;
    }

    public function withBadge(RecaptchaV3Badge $badge): self
    {
        $new = clone $this;
        $new->badge = $badge;

        return $new;
    }

    public function withJsApiUrl(string $url): self
    {
        $new = clone $this;
        $new->jsApiUrl = $url;

        return $new;
    }

    #[\Override]
    public function render(): string
    {
        $siteKey = $this->siteKey ?? throw new \RuntimeException('siteKey is required');
        $fieldId = $this->fieldId ?? Html::generateId('recaptcha-v3-');

        $flags = JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
        $keyJson = json_encode($siteKey, $flags);
        $actionJson = json_encode($this->action, $flags);
        $fieldIdJson = json_encode($fieldId, $flags);

        // No async/defer: with ?render=KEY the inline grecaptcha.ready() below runs
        // at parse time and needs the API to have defined `grecaptcha` first.
        $apiScript = Html::script('')
            ->url($this->jsApiUrl . '?render=' . urlencode($siteKey))
            ->render();

        $input = Html::hiddenInput($this->fieldName)->attribute('id', $fieldId)->render();

        if ($this->formId !== null) {
            $formIdJson = json_encode($this->formId, $flags);
            $js = "(function () { var form = document.getElementById({$formIdJson}); if (!form) { return; }"
                . " form.addEventListener('submit', function (e) { e.preventDefault();"
                . " grecaptcha.ready(function () { grecaptcha.execute({$keyJson}, {action: {$actionJson}})"
                . ".then(function (token) { document.getElementById({$fieldIdJson}).value = token; form.submit(); });"
                . " }); }); })();";
        } else {
            $js = "grecaptcha.ready(function () { grecaptcha.execute({$keyJson}, {action: {$actionJson}})"
                . ".then(function (token) { document.getElementById({$fieldIdJson}).value = token; }); });";
        }

        $badge = match ($this->badge) {
            RecaptchaV3Badge::BottomRight => '',
            RecaptchaV3Badge::BottomLeft => "\n" . Html::style('.grecaptcha-badge { left: 14px !important; right: auto !important; }')->render(),
            RecaptchaV3Badge::Hidden => "\n" . Html::style('.grecaptcha-badge { visibility: hidden; }')->render() . "\n" . self::LEGAL_NOTICE,
        };

        return $apiScript . "\n" . $input . "\n" . Html::script($js)->render() . $badge;
    }
}
