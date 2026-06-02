<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Size;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Theme;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Type;

#[CoversClass(RecaptchaV2::class)]
final class RecaptchaV2Test extends TestCase
{
    #[Test]
    public function rendersWithSiteKey(): void
    {
        $html = RecaptchaV2::widget()->withSiteKey('test-key')->withId('rc')->render();

        $this->assertStringContainsString('"sitekey":"test-key"', $html);
        $this->assertStringContainsString('grecaptcha.render("rc",', $html);
        $this->assertStringContainsString('id="rc"', $html);
    }

    #[Test]
    public function withSiteKeyDoesNotMutateOriginalInstance(): void
    {
        $widget = RecaptchaV2::widget();
        $configuredWidget = $widget->withSiteKey('key');

        $this->assertNotSame($widget, $configuredWidget);
        $this->assertStringContainsString('"sitekey":"key"', $configuredWidget->withId('rc')->render());

        $this->expectException(\RuntimeException::class);
        $widget->render();
    }

    #[Test]
    public function withMethodsDoNotMutateConfiguredInstance(): void
    {
        $widget = RecaptchaV2::widget()->withSiteKey('key')->withId('base');
        $mutatedWidget = $widget
            ->withTheme(RecaptchaV2Theme::Dark)
            ->withType(RecaptchaV2Type::Audio)
            ->withSize(RecaptchaV2Size::Compact)
            ->withJsApiUrl('https://custom.example.com/api.js')
            ->withCallback('onSuccess')
            ->withExpiredCallback('onExpired')
            ->withErrorCallback('onError');

        $baseHtml = $widget->render();
        $mutatedHtml = $mutatedWidget->render();

        $this->assertStringContainsString('"theme":"light"', $baseHtml);
        $this->assertStringContainsString('"type":"image"', $baseHtml);
        $this->assertStringContainsString('"size":"normal"', $baseHtml);
        $this->assertStringContainsString('https://www.google.com/recaptcha/api.js?onload=', $baseHtml);
        $this->assertStringNotContainsString('"callback"', $baseHtml);
        $this->assertStringNotContainsString('"expired-callback"', $baseHtml);
        $this->assertStringNotContainsString('"error-callback"', $baseHtml);
        $this->assertStringContainsString('id="base"', $baseHtml);

        $this->assertStringContainsString('"theme":"dark"', $mutatedHtml);
        $this->assertStringContainsString('"type":"audio"', $mutatedHtml);
        $this->assertStringContainsString('"size":"compact"', $mutatedHtml);
        $this->assertStringContainsString('https://custom.example.com/api.js?onload=', $mutatedHtml);
        $this->assertStringContainsString('"callback":"onSuccess"', $mutatedHtml);
        $this->assertStringContainsString('"expired-callback":"onExpired"', $mutatedHtml);
        $this->assertStringContainsString('"error-callback":"onError"', $mutatedHtml);
        $this->assertStringContainsString('id="base"', $mutatedHtml);
    }

    #[Test]
    public function usesOnloadCallbackSoRenderRunsAfterApiLoads(): void
    {
        $html = RecaptchaV2::widget()->withSiteKey('key')->withId('rc')->render();

        // grecaptcha.render is wrapped in a named function invoked by the API via onload,
        // never called inline (which would throw under async/defer).
        $this->assertStringContainsString('function recaptchaOnload_rc()', $html);
        $this->assertStringContainsString('onload=recaptchaOnload_rc', $html);
        $this->assertStringContainsString('render=explicit', $html);
        $this->assertStringContainsString('async', $html);
        $this->assertStringContainsString('defer', $html);
    }

    #[Test]
    public function rendersExpectedDefaultMarkup(): void
    {
        $html = RecaptchaV2::widget()->withSiteKey('key')->withId('rc')->render();

        $this->assertSame(
            '<script>function recaptchaOnload_rc() { grecaptcha.render("rc", {"sitekey":"key","theme":"light","type":"image","size":"normal"}); }</script>'
            . "\n"
            . '<div id="rc"></div>'
            . "\n"
            . '<script src="https://www.google.com/recaptcha/api.js?onload=recaptchaOnload_rc&amp;render=explicit" async defer></script>',
            $html,
        );
    }

    #[Test]
    public function rendersWithTheme(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withTheme(RecaptchaV2Theme::Dark)
            ->render();

        $this->assertStringContainsString('"theme":"dark"', $html);
    }

    #[Test]
    public function rendersWithType(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withType(RecaptchaV2Type::Audio)
            ->render();

        $this->assertStringContainsString('"type":"audio"', $html);
    }

    #[Test]
    public function rendersWithSize(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withSize(RecaptchaV2Size::Compact)
            ->render();

        $this->assertStringContainsString('"size":"compact"', $html);
    }

    #[Test]
    public function rendersWithCallback(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withCallback('onSuccess')
            ->render();

        $this->assertStringContainsString('"callback":"onSuccess"', $html);
    }

    #[Test]
    public function rendersWithExpiredCallback(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withExpiredCallback('onExpired')
            ->render();

        $this->assertStringContainsString('"expired-callback":"onExpired"', $html);
    }

    #[Test]
    public function rendersWithErrorCallback(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withErrorCallback('onError')
            ->render();

        $this->assertStringContainsString('"error-callback":"onError"', $html);
    }

    #[Test]
    public function rendersCustomJsApiUrl(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withJsApiUrl('https://custom.example.com/api.js')
            ->render();

        $this->assertStringContainsString('https://custom.example.com/api.js?onload=', $html);
        $this->assertStringContainsString('render=explicit', $html);
    }

    #[Test]
    public function throwsWithoutSiteKey(): void
    {
        $this->expectException(\RuntimeException::class);
        RecaptchaV2::widget()->render();
    }

    #[Test]
    public function usesSiteKeyFromConfig(): void
    {
        $config = new RecaptchaConfig(siteKeyV2: 'config-v2-key', secretV2: 'secret');
        $html = (new RecaptchaV2(config: $config))->render();

        $this->assertStringContainsString('"sitekey":"config-v2-key"', $html);
    }

    #[Test]
    public function withSiteKeyOverridesConfig(): void
    {
        $config = new RecaptchaConfig(siteKeyV2: 'config-v2-key', secretV2: 'secret');
        $html = (new RecaptchaV2(config: $config))->withSiteKey('override-key')->render();

        $this->assertStringContainsString('"sitekey":"override-key"', $html);
    }

    #[Test]
    public function generatesUniqueIdPerInstanceWhenNotSet(): void
    {
        $widget = RecaptchaV2::widget()->withSiteKey('key');

        $this->assertNotSame($widget->render(), $widget->render());
    }

    #[Test]
    public function customIdAppearsInDivAndRenderCall(): void
    {
        $html = RecaptchaV2::widget()->withSiteKey('key')->withId('my-captcha')->render();

        $this->assertStringContainsString('id="my-captcha"', $html);
        $this->assertStringContainsString('grecaptcha.render("my-captcha",', $html);
    }

    #[Test]
    public function escapesUnsafeCallbackToPreventScriptBreakout(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withCallback("x</script><script>alert('xss')</script>")
            ->render();

        $this->assertStringNotContainsString('</script><script>', $html);
        $this->assertStringNotContainsString("alert('xss')", $html);
    }

    #[Test]
    public function withIdDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key')->withId('original');
        $modified = $original->withId('changed');

        $this->assertStringContainsString('id="original"', $original->render());
        $this->assertStringContainsString('id="changed"', $modified->render());
    }

    #[Test]
    public function withThemeDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withTheme(RecaptchaV2Theme::Dark);

        $this->assertStringContainsString('"theme":"light"', $original->render());
        $this->assertStringContainsString('"theme":"dark"', $modified->render());
    }

    #[Test]
    public function withTypeDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withType(RecaptchaV2Type::Audio);

        $this->assertStringContainsString('"type":"image"', $original->render());
        $this->assertStringContainsString('"type":"audio"', $modified->render());
    }

    #[Test]
    public function withSizeDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withSize(RecaptchaV2Size::Compact);

        $this->assertStringContainsString('"size":"normal"', $original->render());
        $this->assertStringContainsString('"size":"compact"', $modified->render());
    }

    #[Test]
    public function withJsApiUrlDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withJsApiUrl('https://custom.example.com/api.js');

        $this->assertStringContainsString('https://www.google.com/recaptcha/api.js', $original->render());
        $this->assertStringContainsString('https://custom.example.com/api.js', $modified->render());
    }

    #[Test]
    public function withCallbackDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withCallback('onSuccess');

        $this->assertStringNotContainsString('"callback"', $original->render());
        $this->assertStringContainsString('"callback":"onSuccess"', $modified->render());
    }

    #[Test]
    public function withExpiredCallbackDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withExpiredCallback('onExpired');

        $this->assertStringNotContainsString('"expired-callback"', $original->render());
        $this->assertStringContainsString('"expired-callback":"onExpired"', $modified->render());
    }

    #[Test]
    public function withErrorCallbackDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withErrorCallback('onError');

        $this->assertStringNotContainsString('"error-callback"', $original->render());
        $this->assertStringContainsString('"error-callback":"onError"', $modified->render());
    }

    #[Test]
    public function jsonEncodingUsesXssSafeFlags(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withId('test<"\'&id')
            ->render();

        $this->assertStringContainsString('\\u003C', $html);
        $this->assertStringContainsString('\\u0022', $html);
        $this->assertStringContainsString('\\u0027', $html);
        $this->assertStringContainsString('\\u0026', $html);
    }

    #[Test]
    public function withResponseFieldNameRendersHiddenInput(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withResponseFieldName('gRecaptchaResponse')
            ->render();

        $this->assertStringContainsString('name="gRecaptchaResponse"', $html);
        $this->assertStringContainsString('type="hidden"', $html);
    }

    #[Test]
    public function withResponseFieldNameRendersInlineCopyCallback(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withResponseFieldName('gRecaptchaResponse')
            ->render();

        $this->assertStringContainsString('recaptchaFieldCopy_', $html);
        $this->assertStringContainsString('.value=t', $html);
    }

    #[Test]
    public function withResponseFieldNameChainsUserCallback(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withResponseFieldName('gRecaptchaResponse')
            ->withCallback('myCallback')
            ->render();

        $this->assertStringContainsString('myCallback', $html);
        $this->assertStringContainsString('.value=t', $html);
    }

    #[Test]
    public function withResponseFieldNameDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withResponseFieldName('gRecaptchaResponse');

        $this->assertStringNotContainsString('gRecaptchaResponse', $original->render());
        $this->assertStringContainsString('gRecaptchaResponse', $modified->render());
    }

    #[Test]
    public function withResponseFieldNameXssSafeFieldId(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withResponseFieldName('field<"\'&name')
            ->render();

        $this->assertStringNotContainsString('field<', $html);
    }
}
