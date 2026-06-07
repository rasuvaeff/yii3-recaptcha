<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3Badge;
use Rasuvaeff\Yii3Recaptcha\Tests\Support\NormalizesHtml;

#[CoversClass(RecaptchaV3::class)]
final class RecaptchaV3Test extends TestCase
{
    use NormalizesHtml;

    #[Test]
    public function rendersWithSiteKey(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('test-key')->render();

        $this->assertStringContainsString('https://www.google.com/recaptcha/api.js?render=test-key', $html);
    }

    #[Test]
    public function withSiteKeyDoesNotMutateOriginalInstance(): void
    {
        $widget = RecaptchaV3::widget();
        $configuredWidget = $widget->withSiteKey('key');

        $this->assertNotSame($widget, $configuredWidget);
        $this->assertStringContainsString('render=key', $configuredWidget->render());

        $this->expectException(\RuntimeException::class);
        $widget->render();
    }

    #[Test]
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

        $this->assertStringContainsString('https://www.google.com/recaptcha/api.js?render=key', $baseHtml);
        $this->assertStringContainsString('name="g-recaptcha-response"', $baseHtml);
        $this->assertStringContainsString('{action: "submit"}', $baseHtml);
        $this->assertStringNotContainsString('document.getElementById("login-form")', $baseHtml);
        $this->assertStringNotContainsString('visibility: hidden', $baseHtml);

        $this->assertStringContainsString('https://custom.example.com/api.js?render=key', $mutatedHtml);
        $this->assertStringContainsString('name="captchaToken"', $mutatedHtml);
        $this->assertStringContainsString('id="token-id"', $mutatedHtml);
        $this->assertStringContainsString('{action: "login"}', $mutatedHtml);
        $this->assertStringContainsString('document.getElementById("login-form")', $mutatedHtml);
        $this->assertStringContainsString('visibility: hidden', $mutatedHtml);
    }

    #[Test]
    public function apiScriptHasNoAsyncDeferSoInlineExecuteIsSafe(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->render();

        $scriptTag = substr($html, 0, (int) strpos($html, '</script>'));
        $this->assertStringNotContainsString('async', $scriptTag);
        $this->assertStringNotContainsString('defer', $scriptTag);
    }

    #[Test]
    public function rendersCustomJsApiUrl(): void
    {
        $html = RecaptchaV3::widget()
            ->withSiteKey('key')
            ->withJsApiUrl('https://custom.example.com/api.js')
            ->render();

        $this->assertStringContainsString('https://custom.example.com/api.js?render=key', $html);
    }

    #[Test]
    public function throwsWithoutSiteKey(): void
    {
        $this->expectException(\RuntimeException::class);
        RecaptchaV3::widget()->render();
    }

    #[Test]
    public function usesSiteKeyFromConfig(): void
    {
        $config = new RecaptchaConfig(siteKeyV3: 'config-v3-key', secretV3: 'secret');
        $html = (new RecaptchaV3(config: $config))->render();

        $this->assertStringContainsString('render=config-v3-key', $html);
    }

    #[Test]
    public function withSiteKeyOverridesConfig(): void
    {
        $config = new RecaptchaConfig(siteKeyV3: 'config-v3-key', secretV3: 'secret');
        $html = (new RecaptchaV3(config: $config))->withSiteKey('override-key')->render();

        $this->assertStringContainsString('render=override-key', $html);
    }

    #[Test]
    public function rendersHiddenInputWithDefaultName(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->render();

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="g-recaptcha-response"', $html);
    }

    #[Test]
    public function rendersCustomFieldName(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withFieldName('captchaToken')->render();

        $this->assertStringContainsString('name="captchaToken"', $html);
    }

    #[Test]
    public function executesWithDefaultAction(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->render();

        $this->assertStringContainsString('grecaptcha.execute("key", {action: "submit"})', $html);
        $this->assertStringContainsString('grecaptcha.ready(', $html);
    }

    #[Test]
    public function executesWithCustomAction(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withAction('login')->render();

        $this->assertStringContainsString('{action: "login"}', $html);
    }

    #[Test]
    public function bindsInvisibleSubmitWhenFormIdSet(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withFormId('login-form')->render();

        $this->assertStringContainsString('document.getElementById("login-form")', $html);
        $this->assertStringContainsString("addEventListener('submit'", $html);
        $this->assertStringNotContainsString('dataset.recaptchaV3Done', $html);
        $this->assertStringContainsString('form.submit();', $html);
    }

    #[Test]
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

        // Attribute order inside <input> varies across yiisoft/html versions.
        $this->assertSame(self::normalizeInputAttributes($expected), self::normalizeInputAttributes($html));
    }

    #[Test]
    public function executesOnReadyWhenNoFormId(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->render();

        $this->assertStringNotContainsString('form.submit();', $html);
        $this->assertStringNotContainsString("addEventListener('submit'", $html);
    }

    #[Test]
    public function customFieldIdAppearsInInputAndScript(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withFieldId('my-token')->render();

        $this->assertStringContainsString('id="my-token"', $html);
        $this->assertStringContainsString('document.getElementById("my-token")', $html);
    }

    #[Test]
    public function generatesUniqueFieldIdPerInstanceWhenNotSet(): void
    {
        $widget = RecaptchaV3::widget()->withSiteKey('key');

        $this->assertNotSame($widget->render(), $widget->render());
    }

    #[Test]
    public function bottomRightBadgeAddsNoStyle(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->render();

        $this->assertStringNotContainsString('.grecaptcha-badge', $html);
    }

    #[Test]
    public function bottomLeftBadgeAddsStyle(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withBadge(RecaptchaV3Badge::BottomLeft)->render();

        $this->assertStringContainsString('.grecaptcha-badge', $html);
        $this->assertStringContainsString('left: 14px', $html);
    }

    #[Test]
    public function hiddenBadgeAddsStyleAndLegalNotice(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withBadge(RecaptchaV3Badge::Hidden)->render();

        $this->assertStringContainsString('visibility: hidden', $html);
        $this->assertStringContainsString('Privacy Policy', $html);
        $this->assertStringContainsString('Terms of Service', $html);
    }

    #[Test]
    public function escapesUnsafeActionToPreventScriptBreakout(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withAction('"});alert(1);//')->render();

        $this->assertStringNotContainsString('"});alert', $html);
        $this->assertStringContainsString('{action: "\\u0022});alert(1);\\/\\/"}', $html);
    }

    #[Test]
    public function withFieldNameDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV3::widget()->withSiteKey('key');
        $modified = $original->withFieldName('captchaToken');

        $this->assertStringContainsString('name="g-recaptcha-response"', $original->render());
        $this->assertStringContainsString('name="captchaToken"', $modified->render());
    }

    #[Test]
    public function withFieldIdDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV3::widget()->withSiteKey('key')->withFieldId('original-id');
        $modified = $original->withFieldId('new-id');

        $this->assertStringContainsString('id="original-id"', $original->render());
        $this->assertStringContainsString('id="new-id"', $modified->render());
    }

    #[Test]
    public function withFormIdDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV3::widget()->withSiteKey('key');
        $modified = $original->withFormId('login-form');

        $this->assertStringNotContainsString('document.getElementById("login-form")', $original->render());
        $this->assertStringContainsString('document.getElementById("login-form")', $modified->render());
    }

    #[Test]
    public function withBadgeDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV3::widget()->withSiteKey('key');
        $modified = $original->withBadge(RecaptchaV3Badge::Hidden);

        $this->assertStringNotContainsString('visibility: hidden', $original->render());
        $this->assertStringContainsString('visibility: hidden', $modified->render());
    }

    #[Test]
    public function withJsApiUrlDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV3::widget()->withSiteKey('key');
        $modified = $original->withJsApiUrl('https://custom.example.com/api.js');

        $this->assertStringContainsString('https://www.google.com/recaptcha/api.js', $original->render());
        $this->assertStringContainsString('https://custom.example.com/api.js', $modified->render());
    }

    #[Test]
    public function jsonEncodingUsesXssSafeFlags(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('<"key&\'')->render();

        $this->assertStringContainsString('\\u003C', $html);
        $this->assertStringContainsString('\\u0022', $html);
        $this->assertStringContainsString('\\u0026', $html);
    }

    #[Test]
    public function noFormIdRenderStartsWithGrecaptchaReady(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withFieldId('tid')->render();

        $scriptContent = '';
        if (preg_match('/<script>(.*?)<\/script>/s', $html, $m)) {
            $scriptContent = $m[1];
        }
        $this->assertStringStartsWith('grecaptcha.ready(', trim($scriptContent));
    }

    #[Test]
    public function bottomLeftBadgeOutputPrecedesStyleWithNewline(): void
    {
        $html = RecaptchaV3::widget()->withSiteKey('key')->withBadge(RecaptchaV3Badge::BottomLeft)->render();

        $this->assertMatchesRegularExpression("/\\n<style>/", $html);
    }
}
