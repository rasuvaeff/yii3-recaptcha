<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Rasuvaeff\PropertyTesting\ArbitraryInterface;
use Rasuvaeff\PropertyTesting\Gen;
use Rasuvaeff\PropertyTesting\Property;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Size;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Theme;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Type;
use Rasuvaeff\Yii3Recaptcha\Tests\Support\NormalizesHtml;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(RecaptchaV2::class)]
final class RecaptchaV2Test
{
    use NormalizesHtml;

    public function rendersWithSiteKey(): void
    {
        $html = RecaptchaV2::widget()->withSiteKey('test-key')->withId('rc')->render();

        Assert::string($html)->contains('"sitekey":"test-key"');
        Assert::string($html)->contains('grecaptcha.render("rc",');
        Assert::string($html)->contains('id="rc"');
    }

    public function withSiteKeyDoesNotMutateOriginalInstance(): void
    {
        $widget = RecaptchaV2::widget();
        $configuredWidget = $widget->withSiteKey('key');

        Assert::notSame($widget, $configuredWidget);
        Assert::string($configuredWidget->withId('rc')->render())->contains('"sitekey":"key"');

        Expect::exception(\RuntimeException::class);
        $widget->render();
    }

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

        Assert::string($baseHtml)->contains('"theme":"light"');
        Assert::string($baseHtml)->contains('"type":"image"');
        Assert::string($baseHtml)->contains('"size":"normal"');
        Assert::string($baseHtml)->contains('https://www.google.com/recaptcha/api.js?onload=');
        Assert::string($baseHtml)->notContains('"callback"');
        Assert::string($baseHtml)->notContains('"expired-callback"');
        Assert::string($baseHtml)->notContains('"error-callback"');
        Assert::string($baseHtml)->contains('id="base"');

        Assert::string($mutatedHtml)->contains('"theme":"dark"');
        Assert::string($mutatedHtml)->contains('"type":"audio"');
        Assert::string($mutatedHtml)->contains('"size":"compact"');
        Assert::string($mutatedHtml)->contains('https://custom.example.com/api.js?onload=');
        Assert::string($mutatedHtml)->contains('"callback":"onSuccess"');
        Assert::string($mutatedHtml)->contains('"expired-callback":"onExpired"');
        Assert::string($mutatedHtml)->contains('"error-callback":"onError"');
        Assert::string($mutatedHtml)->contains('id="base"');
    }

    public function usesOnloadCallbackSoRenderRunsAfterApiLoads(): void
    {
        $html = RecaptchaV2::widget()->withSiteKey('key')->withId('rc')->render();

        Assert::string($html)->contains('function recaptchaOnload_rc()');
        Assert::string($html)->contains('onload=recaptchaOnload_rc');
        Assert::string($html)->contains('render=explicit');
        Assert::string($html)->contains('async');
        Assert::string($html)->contains('defer');
    }

    public function rendersExpectedDefaultMarkup(): void
    {
        $html = RecaptchaV2::widget()->withSiteKey('key')->withId('rc')->render();

        Assert::same(
            $html,
            '<script>function recaptchaOnload_rc() { grecaptcha.render("rc", {"sitekey":"key","theme":"light","type":"image","size":"normal"}); }</script>'
            . "\n"
            . '<div id="rc"></div>'
            . "\n"
            . '<script src="https://www.google.com/recaptcha/api.js?onload=recaptchaOnload_rc&amp;render=explicit" async defer></script>',
        );
    }

    public function rendersWithTheme(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withTheme(RecaptchaV2Theme::Dark)
            ->render();

        Assert::string($html)->contains('"theme":"dark"');
    }

    public function rendersWithType(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withType(RecaptchaV2Type::Audio)
            ->render();

        Assert::string($html)->contains('"type":"audio"');
    }

    public function rendersWithSize(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withSize(RecaptchaV2Size::Compact)
            ->render();

        Assert::string($html)->contains('"size":"compact"');
    }

    public function rendersWithCallback(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withCallback('onSuccess')
            ->render();

        Assert::string($html)->contains('"callback":"onSuccess"');
    }

    public function rendersWithExpiredCallback(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withExpiredCallback('onExpired')
            ->render();

        Assert::string($html)->contains('"expired-callback":"onExpired"');
    }

    public function rendersWithErrorCallback(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withErrorCallback('onError')
            ->render();

        Assert::string($html)->contains('"error-callback":"onError"');
    }

    public function rendersCustomJsApiUrl(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withJsApiUrl('https://custom.example.com/api.js')
            ->render();

        Assert::string($html)->contains('https://custom.example.com/api.js?onload=');
        Assert::string($html)->contains('render=explicit');
    }

    public function throwsWithoutSiteKey(): void
    {
        Expect::exception(\RuntimeException::class);
        RecaptchaV2::widget()->render();
    }

    public function emptySiteKeyInConfigDoesNotSetSiteKey(): void
    {
        $config = new RecaptchaConfig(siteKeyV2: '', secretV2: 'secret');

        Expect::exception(\RuntimeException::class);
        (new RecaptchaV2(config: $config))->render();
    }

    public function usesSiteKeyFromConfig(): void
    {
        $config = new RecaptchaConfig(siteKeyV2: 'config-v2-key', secretV2: 'secret');
        $html = (new RecaptchaV2(config: $config))->render();

        Assert::string($html)->contains('"sitekey":"config-v2-key"');
    }

    public function withSiteKeyOverridesConfig(): void
    {
        $config = new RecaptchaConfig(siteKeyV2: 'config-v2-key', secretV2: 'secret');
        $html = (new RecaptchaV2(config: $config))->withSiteKey('override-key')->render();

        Assert::string($html)->contains('"sitekey":"override-key"');
    }

    public function generatesUniqueIdPerInstanceWhenNotSet(): void
    {
        $widget = RecaptchaV2::widget()->withSiteKey('key');

        Assert::notSame($widget->render(), $widget->render());
    }

    public function customIdAppearsInDivAndRenderCall(): void
    {
        $html = RecaptchaV2::widget()->withSiteKey('key')->withId('my-captcha')->render();

        Assert::string($html)->contains('id="my-captcha"');
        Assert::string($html)->contains('grecaptcha.render("my-captcha",');
    }

    public function escapesUnsafeCallbackToPreventScriptBreakout(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withCallback("x</script><script>alert('xss')</script>")
            ->render();

        Assert::string($html)->notContains('</script><script>');
        Assert::string($html)->notContains("alert('xss')");
    }

    public function withIdDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key')->withId('original');
        $modified = $original->withId('changed');

        Assert::string($original->render())->contains('id="original"');
        Assert::string($modified->render())->contains('id="changed"');
    }

    public function withThemeDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withTheme(RecaptchaV2Theme::Dark);

        Assert::string($original->render())->contains('"theme":"light"');
        Assert::string($modified->render())->contains('"theme":"dark"');
    }

    public function withTypeDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withType(RecaptchaV2Type::Audio);

        Assert::string($original->render())->contains('"type":"image"');
        Assert::string($modified->render())->contains('"type":"audio"');
    }

    public function withSizeDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withSize(RecaptchaV2Size::Compact);

        Assert::string($original->render())->contains('"size":"normal"');
        Assert::string($modified->render())->contains('"size":"compact"');
    }

    public function withJsApiUrlDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withJsApiUrl('https://custom.example.com/api.js');

        Assert::string($original->render())->contains('https://www.google.com/recaptcha/api.js');
        Assert::string($modified->render())->contains('https://custom.example.com/api.js');
    }

    public function withCallbackDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withCallback('onSuccess');

        Assert::string($original->render())->notContains('"callback"');
        Assert::string($modified->render())->contains('"callback":"onSuccess"');
    }

    public function withExpiredCallbackDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withExpiredCallback('onExpired');

        Assert::string($original->render())->notContains('"expired-callback"');
        Assert::string($modified->render())->contains('"expired-callback":"onExpired"');
    }

    public function withErrorCallbackDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withErrorCallback('onError');

        Assert::string($original->render())->notContains('"error-callback"');
        Assert::string($modified->render())->contains('"error-callback":"onError"');
    }

    public function jsonEncodingUsesXssSafeFlags(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withId('test<"\'&id')
            ->render();

        Assert::string($html)->contains('\\u003C');
        Assert::string($html)->contains('\\u0022');
        Assert::string($html)->contains('\\u0027');
        Assert::string($html)->contains('\\u0026');
    }

    public function withResponseFieldNameRendersHiddenInput(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withResponseFieldName('gRecaptchaResponse')
            ->render();

        Assert::string($html)->contains('name="gRecaptchaResponse"');
        Assert::string($html)->contains('type="hidden"');
    }

    public function withResponseFieldNameRendersInlineCopyCallback(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withResponseFieldName('gRecaptchaResponse')
            ->render();

        Assert::string($html)->contains('recaptchaFieldCopy_');
        Assert::string($html)->contains('.value=t');
    }

    public function withResponseFieldNameChainsUserCallback(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withResponseFieldName('gRecaptchaResponse')
            ->withCallback('myCallback')
            ->render();

        Assert::string($html)->contains('myCallback');
        Assert::string($html)->contains('.value=t');
    }

    public function withResponseFieldNameDoesNotMutateOriginal(): void
    {
        $original = RecaptchaV2::widget()->withSiteKey('key');
        $modified = $original->withResponseFieldName('gRecaptchaResponse');

        Assert::string($original->render())->notContains('gRecaptchaResponse');
        Assert::string($modified->render())->contains('gRecaptchaResponse');
    }

    public function withResponseFieldNameXssSafeFieldId(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withResponseFieldName('field<"\'&name')
            ->render();

        Assert::string($html)->notContains('field<');
    }

    public function withResponseFieldNameUsesIdPrefixedFieldId(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withId('my-rc')
            ->withResponseFieldName('g-recaptcha-response')
            ->render();

        Assert::string($html)->contains('id="my-rc-response"');
        Assert::string($html)->notContains('id="-response"');
    }

    public function withResponseFieldNameEmbedsCopyCallbackWithIdSuffix(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withId('my-rc')
            ->withResponseFieldName('g-recaptcha-response')
            ->render();

        Assert::string($html)->contains('recaptchaFieldCopy_my_rc');
        Assert::string($html)->notContains('function recaptchaFieldCopy_(');
    }

    public function withResponseFieldNameCopyCallbackChainIncludesCallSuffix(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withId('rc')
            ->withResponseFieldName('resp')
            ->withCallback('myFn')
            ->render();

        Assert::string($html)->contains('"myFn"(t);');
    }

    public function withResponseFieldNameHiddenBlockPrecedesInitScript(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withId('rc')
            ->withResponseFieldName('resp')
            ->render();

        $hiddenPos = strpos($html, 'type="hidden"');
        $initPos = strpos($html, 'function recaptchaOnload_');

        Assert::notSame($hiddenPos, false);
        Assert::notSame($initPos, false);
        Assert::true($hiddenPos < $initPos);
    }

    public function withResponseFieldNameRendersExactMarkup(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withId('rc')
            ->withResponseFieldName('resp')
            ->render();

        Assert::string(
            self::normalizeInputAttributes($html),
        )->contains(
            self::normalizeInputAttributes(
                '<input type="hidden" name="resp" id="rc-response">'
                . "\n"
                . '<script>function recaptchaFieldCopy_rc(t){document.getElementById("rc-response").value=t;}</script>',
            ),
        );

        Assert::string($html)->contains(
            '</script>' . "\n" . '<script>function recaptchaOnload_rc()',
        );
    }

    public function withResponseFieldNameFullOutputOrder(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('key')
            ->withId('rc')
            ->withResponseFieldName('resp')
            ->render();

        $expected
            = '<input type="hidden" name="resp" id="rc-response">'
            . "\n"
            . '<script>function recaptchaFieldCopy_rc(t){document.getElementById("rc-response").value=t;}</script>'
            . "\n"
            . '<script>function recaptchaOnload_rc() { grecaptcha.render("rc", {"sitekey":"key","theme":"light","type":"image","size":"normal","callback":"recaptchaFieldCopy_rc"}); }</script>'
            . "\n"
            . '<div id="rc"></div>'
            . "\n"
            . '<script src="https://www.google.com/recaptcha/api.js?onload=recaptchaOnload_rc&amp;render=explicit" async defer></script>';

        Assert::same(self::normalizeInputAttributes($html), self::normalizeInputAttributes($expected));
    }

    public function withNonceAddsNonceToEveryScript(): void
    {
        $html = RecaptchaV2::widget()->withSiteKey('k')->withId('rc')->withNonce('n123')->render();

        Assert::same(substr_count($html, 'nonce="n123"'), 2);
    }

    /**
     * The number of `<script` markers is invariant to user-controlled input:
     * the JSON_HEX_* flags escape any `<` in the site key or callback, so no
     * string can break out of the inline script and inject its own tag.
     */
    #[Property(runs: 200)]
    public function userInputCannotInjectScriptTags(string $payload): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey($payload)
            ->withCallback($payload)
            ->withId('rc')
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
