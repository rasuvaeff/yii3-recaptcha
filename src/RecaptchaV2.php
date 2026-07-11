<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

use Rasuvaeff\Yii3Recaptcha\Exception\MissingSiteKeyException;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Script;
use Yiisoft\Widget\Widget;

/**
 * @api
 */
final class RecaptchaV2 extends Widget
{
    private const string JS_API_URL = 'https://www.google.com/recaptcha/api.js';

    private ?string $siteKey = null;
    private ?string $id = null;
    private RecaptchaV2Theme $theme = RecaptchaV2Theme::Light;
    private RecaptchaV2Type $type = RecaptchaV2Type::Image;
    private RecaptchaV2Size $size = RecaptchaV2Size::Normal;
    private string $jsApiUrl = self::JS_API_URL;
    private ?string $responseFieldName = null;
    private ?string $callback = null;
    private ?string $expiredCallback = null;
    private ?string $errorCallback = null;
    private ?string $nonce = null;

    public function __construct(
        ?RecaptchaConfig $config = null,
    ) {
        if ($config !== null && $config->siteKeyV2 !== '') {
            $this->siteKey = $config->siteKeyV2;
        }
    }

    public function withSiteKey(string $siteKey): self
    {
        $new = clone $this;
        $new->siteKey = $siteKey;

        return $new;
    }

    public function withId(string $id): self
    {
        $new = clone $this;
        $new->id = $id;

        return $new;
    }

    public function withTheme(RecaptchaV2Theme $theme): self
    {
        $new = clone $this;
        $new->theme = $theme;

        return $new;
    }

    public function withType(RecaptchaV2Type $type): self
    {
        $new = clone $this;
        $new->type = $type;

        return $new;
    }

    public function withSize(RecaptchaV2Size $size): self
    {
        $new = clone $this;
        $new->size = $size;

        return $new;
    }

    public function withJsApiUrl(string $url): self
    {
        $new = clone $this;
        $new->jsApiUrl = $url;

        return $new;
    }

    public function withResponseFieldName(string $name): self
    {
        $new = clone $this;
        $new->responseFieldName = $name;

        return $new;
    }

    public function withCallback(string $callback): self
    {
        $new = clone $this;
        $new->callback = $callback;

        return $new;
    }

    public function withExpiredCallback(string $callback): self
    {
        $new = clone $this;
        $new->expiredCallback = $callback;

        return $new;
    }

    public function withErrorCallback(string $callback): self
    {
        $new = clone $this;
        $new->errorCallback = $callback;

        return $new;
    }

    /**
     * Sets the CSP `nonce` attribute on every emitted `<script>` tag, so the
     * widget works under a strict Content-Security-Policy without `unsafe-inline`.
     */
    public function withNonce(string $nonce): self
    {
        $new = clone $this;
        $new->nonce = $nonce;

        return $new;
    }

    #[\Override]
    public function render(): string
    {
        $siteKey = $this->siteKey ?? throw new MissingSiteKeyException();
        $id = $this->id ?? Html::generateId('recaptcha-v2-');
        $callback = 'recaptchaOnload_' . (string) preg_replace('/[^A-Za-z0-9_]/', '_', $id);

        $flags = JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

        $userCallback = $this->callback;
        $hiddenInput = '';

        if ($this->responseFieldName !== null) {
            $fieldId = $id . '-response';
            $fieldIdJson = json_encode($fieldId, $flags);
            $copyCallback = 'recaptchaFieldCopy_' . (string) preg_replace('/[^A-Za-z0-9_]/', '_', $id);

            $chain = $userCallback !== null ? json_encode($userCallback, $flags) . '(t);' : '';
            $hiddenInput = Html::hiddenInput($this->responseFieldName)
                ->attribute('id', $fieldId)
                ->render();
            $hiddenInput .= "\n" . $this->nonced(Html::script(
                "function {$copyCallback}(t){document.getElementById({$fieldIdJson}).value=t;{$chain}}",
            ))->render();

            $userCallback = $copyCallback;
        }

        $params = array_filter([
            'sitekey' => $siteKey,
            'theme' => $this->theme->value,
            'type' => $this->type->value,
            'size' => $this->size->value,
            'callback' => $userCallback,
            'expired-callback' => $this->expiredCallback,
            'error-callback' => $this->errorCallback,
        ]);

        $idJson = json_encode($id, $flags);
        $paramsJson = json_encode($params, $flags);

        // Define the explicit-render callback up front; the API invokes it via the
        // `onload` parameter once loaded, so `grecaptcha` is guaranteed to exist
        // (calling grecaptcha.render() inline under async/defer would throw).
        $initScript = $this->nonced(
            Html::script("function {$callback}() { grecaptcha.render({$idJson}, {$paramsJson}); }"),
        )->render();

        $apiScript = $this->nonced(
            Html::script('')
                ->url($this->jsApiUrl . '?onload=' . urlencode($callback) . '&render=explicit')
                ->attribute('async', '')
                ->attribute('defer', ''),
        )->render();

        return $hiddenInput . ($hiddenInput !== '' ? "\n" : '')
            . $initScript . "\n" . Html::div('')->attribute('id', $id)->render() . "\n" . $apiScript;
    }

    private function nonced(Script $tag): Script
    {
        return $this->nonce === null ? $tag : $tag->attribute('nonce', $this->nonce);
    }
}
