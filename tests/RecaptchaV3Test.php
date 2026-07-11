<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Badge;
use Rasuvaeff\Yii3Recaptcha\Tests\Support\NormalizesHtml;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(RecaptchaV3::class)]
final class RecaptchaV3Test
{
    use NormalizesHtml;

    public function rendersWithSiteKey(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('test-key')->render();

        Assert::string($html)->contains('https://www.google.com/recaptcha/api.js?render=test-key');
    }

    public function withSiteKeyDoesNotMutateOriginalInstance(): void
    {
        $widget = RecaptchaV3::widget();
        $configuredWidget = $widget->withSiteKey('key');

        Assert::notSame($widget, $configuredWidget);
        Assert::string($configuredWidget->render())->contains('render=key');

        Expect::exception(\RuntimeException::class);
        $widget->render();
    }

    public function withMethodsDoNotMutateConfiguredInstance(): void
    {
        $widget = RecaptchaV3::widget()->withSiteKey('key');
        $mutatedWidget = $widget
            ->withAction('login')
            ->withFieldName('captchaToken')
            ->withFieldId('token-id')
            ->withFormId('login-form')
            ->withBadge(RecaptchaV3Badge::Hidden)
            ->withJsApiUrl('https://custom.example.com/api.js');

        $baseHtml = $widget->render();
        $mutatedHtml = $mutatedWidget->render();

        Assert::string($baseHtml)->contains('https://www.google.com/recaptcha/api.js?render=key');
        Assert::string($baseHtml)->contains('name="g-recaptcha-response"');
        Assert::string($baseHtml)->contains('{action: "submit"}');
        Assert::string($baseHtml)->notContains('document.getElementById("login-form")');
        Assert::string($baseHtml)->notContains('visibility: hidden');

        Assert::string($mutatedHtml)->contains('https://custom.example.com/api.js?render=key');
        Assert::string($mutatedHtml)->contains('name="captchaToken"');
        Assert::string($mutatedHtml)->contains('id="token-id"');
        Assert::string($mutatedHtml)->contains('{action: "login"}');
        Assert::string($mutatedHtml)->contains('document.getElementById("login-form")');
        Assert::string($mutatedHtml)->contains('visibility: hidden');
    }

    public function apiScriptHasNoAsyncDeferSoInlineExecuteIsSafe(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->render();

        $scriptTag = substr($html, 0, (int) strpos($html, '</script>'));
        Assert::string($scriptTag)->notContains('async');
        Assert::string($scriptTag)->notContains('defer');
    }

    public function rendersCustomJsApiUrl(): void
    {
        $html = RecaptchaV3::widget()
            ->withSiteKey('key')
            ->withJsApiUrl('https://custom.example.com/api.js')
            ->render();

        Assert::string($html)->contains('https://custom.example.com/api.js?render=key');
    }

    public function throwsWithoutSiteKey(): void
    {
        Expect::exception(\RuntimeException::class);
        RecaptchaV3::widget()->render();
    }

    public function emptySiteKeyInConfigDoesNotSetSiteKey(): void
    {
        $config = new RecaptchaConfig(siteKeyV3: '', secretV3: 'secret');

        Expect::exception(\RuntimeException::class);
        (new RecaptchaV3(config: $config))->render();
    }

    public function usesSiteKeyFromConfig(): void
    {
        $config = new RecaptchaConfig(siteKeyV3: 'config-v3-key', secretV3: 'secret');
        $html = (new RecaptchaV3(config: $config))->render();

        Assert::string($html)->contains('render=config-v3-key');
    }

    public function withSiteKeyOverridesConfig(): void
    {
        $config = new RecaptchaConfig(siteKeyV3: 'config-v3-key', secretV3: 'secret');
        $html = (new RecaptchaV3(config: $config))->withSiteKey('override-key')->render();

        Assert::string($html)->contains('render=override-key');
    }

    public function rendersHiddenInputWithDefaultName(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->render();

        Assert::string($html)->contains('type="hidden"');
        Assert::string($html)->contains('name="g-recaptcha-response"');
    }

    public function rendersCustomFieldName(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withFieldName('captchaToken')->render();

        Assert::string($html)->contains('name="captchaToken"');
    }

    public function executesWithDefaultAction(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->render();

        Assert::string($html)->contains('grecaptcha.execute("key", {action: "submit"})');
        Assert::string($html)->contains('grecaptcha.ready(');
    }

    public function executesWithCustomAction(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withAction('login')->render();

        Assert::string($html)->contains('{action: "login"}');
    }

    public function bindsInvisibleSubmitWhenFormIdSet(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withFormId('login-form')->render();

        Assert::string($html)->contains('document.getElementById("login-form")');
        Assert::string($html)->contains("addEventListener('submit'");
        Assert::string($html)->notContains('dataset.recaptchaV3Done');
        Assert::string($html)->contains('form.submit();');
    }

    public function rendersExpectedHiddenBadgeMarkup(): void
    {
        $html = RecaptchaV3::widget()
            ->withSiteKey('key')
            ->withFieldId('token-id')
            ->withFormId('login-form')
            ->withBadge(RecaptchaV3Badge::Hidden)
            ->render();

        $expected = '<script src="https://www.google.com/recaptcha/api.js?render=key"></script>'
            . "\n"
            . '<input type="hidden" name="g-recaptcha-response" id="token-id">'
            . "\n"
            . '<script>(function () { var form = document.getElementById("login-form"); if (!form) { return; } form.addEventListener(\'submit\', function (e) { e.preventDefault(); grecaptcha.ready(function () { grecaptcha.execute("key", {action: "submit"}).then(function (token) { document.getElementById("token-id").value = token; form.submit(); }); }); }); })();</script>'
            . "\n"
            . '<style>.grecaptcha-badge { visibility: hidden; }</style>'
            . "\n"
            . '<p class="recaptcha-v3-notice">This site is protected by reCAPTCHA and the Google <a href="https://policies.google.com/privacy">Privacy Policy</a> and <a href="https://policies.google.com/terms">Terms of Service</a> apply.</p>';

        Assert::same(self::normalizeInputAttributes($html), self::normalizeInputAttributes($expected));
    }

    public function executesOnReadyWhenNoFormId(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->render();

        Assert::string($html)->notContains('form.submit();');
        Assert::string($html)->notContains("addEventListener('submit'");
    }

    public function customFieldIdAppearsInInputAndScript(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withFieldId('my-token')->render();

        Assert::string($html)->contains('id="my-token"');
        Assert::string($html)->contains('document.getElementById("my-token")');
    }

    public function generatesUniqueFieldIdPerInstanceWhenNotSet(): void
    {
        $widget = RecaptchaV3::widget()->withSiteKey('key');

        Assert::notSame($widget->render(), $widget->render());
    }

    public function bottomRightBadgeAddsNoStyle(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->render();

        Assert::string($html)->notContains('.grecaptcha-badge');
    }

    public function bottomLeftBadgeAddsStyle(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withBadge(RecaptchaV3Badge::BottomLeft)->render();

        Assert::string($html)->contains('.grecaptcha-badge');
        Assert::string($html)->contains('left: 14px');
    }

    public function hiddenBadgeAddsStyleAndLegalNotice(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withBadge(RecaptchaV3Badge::Hidden)->render();

        Assert::string($html)->contains('visibility: hidden');
        Assert::string($html)->contains('Privacy Policy');
        Assert::string($html)->contains('Terms of Service');
    }

    public function escapesUnsafeActionToPreventScriptBreakout(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withAction('"});alert(1);//')->render();

        Assert::string($html)->notContains('"});alert');
        Assert::string($html)->contains('{action: "\\u0022});alert(1);\\/\\/"}');
    }

    public function withFieldNameDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV3::widget()->withSiteKey('key');
        $modified = $original->withFieldName('captchaToken');

        Assert::string($original->render())->contains('name="g-recaptcha-response"');
        Assert::string($modified->render())->contains('name="captchaToken"');
    }

    public function withFieldIdDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV3::widget()->withSiteKey('key')->withFieldId('original-id');
        $modified = $original->withFieldId('new-id');

        Assert::string($original->render())->contains('id="original-id"');
        Assert::string($modified->render())->contains('id="new-id"');
    }

    public function withFormIdDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV3::widget()->withSiteKey('key');
        $modified = $original->withFormId('login-form');

        Assert::string($original->render())->notContains('document.getElementById("login-form")');
        Assert::string($modified->render())->contains('document.getElementById("login-form")');
    }

    public function withBadgeDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV3::widget()->withSiteKey('key');
        $modified = $original->withBadge(RecaptchaV3Badge::Hidden);

        Assert::string($original->render())->notContains('visibility: hidden');
        Assert::string($modified->render())->contains('visibility: hidden');
    }

    public function withJsApiUrlDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV3::widget()->withSiteKey('key');
        $modified = $original->withJsApiUrl('https://custom.example.com/api.js');

        Assert::string($original->render())->contains('https://www.google.com/recaptcha/api.js');
        Assert::string($modified->render())->contains('https://custom.example.com/api.js');
    }

    public function jsonEncodingUsesXssSafeFlags(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('<"key&\'')->render();

        Assert::string($html)->contains('\\u003C');
        Assert::string($html)->contains('\\u0022');
        Assert::string($html)->contains('\\u0026');
    }

    public function noFormIdRenderStartsWithGrecaptchaReady(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withFieldId('tid')->render();

        $scriptContent = '';
        if (preg_match('/<script>(.*?)<\/script>/s', $html, $m)) {
            $scriptContent = $m[1];
        }
        Assert::true(str_starts_with(trim($scriptContent), 'grecaptcha.ready('));
    }

    public function bottomLeftBadgeOutputPrecedesStyleWithNewline(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withBadge(RecaptchaV3Badge::BottomLeft)->render();

        Assert::true(preg_match("/\\n<style>/", $html) === 1);
    }

    public function withNonceAddsNonceToScriptsAndStyles(): void
    {
        $html = RecaptchaV3::widget()
            ->withSiteKey('k')
            ->withBadge(RecaptchaV3Badge::BottomLeft)
            ->withNonce('n123')
            ->render();

        // apiScript + inline script + badge <style> all carry the nonce.
        Assert::same(substr_count($html, 'nonce="n123"'), 3);
    }

    /**
     * The number of `<script` markers is invariant to user-controlled input:
     * the JSON_HEX_* flags escape any `<` in the site key or action, so no
     * string can break out of the inline script and inject its own tag.
     */
    #[Property(runs: 200)]
    public function userInputCannotInjectScriptTags(string $payload): void
    {
        $html = RecaptchaV3::widget()
            ->withSiteKey($payload)
            ->withAction($payload)
            ->render();

        Assert::same(substr_count($html, '<script'), 2);
    }

    /**
     * @return array<string, ArbitraryInterface>
     */
    public static function userInputCannotInjectScriptTagsGenerators(): array
    {
        return [
            'payload' => Gen::stringAscii(),
        ];
    }
}
