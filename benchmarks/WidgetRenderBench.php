<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Benchmarks;

use Rasuvaeff\Yii3Recaptcha\RecaptchaV2;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Size;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Theme;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Type;
use Testo\Bench;

/**
 * Compares rendering RecaptchaV2 with minimal options vs all options set,
 * including the hidden response field path (extra JS + hidden input branch).
 */
final class WidgetRenderBench
{
    #[Bench(
        callables: [
            'full-options' => [self::class, 'renderWithAllOptions'],
        ],
        calls: 1_000,
        iterations: 10,
    )]
    public static function renderMinimal(): string
    {
        return (new RecaptchaV2())
            ->withSiteKey('6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI')
            ->withId('recaptcha-login')
            ->render();
    }

    public static function renderWithAllOptions(): string
    {
        return (new RecaptchaV2())
            ->withSiteKey('6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI')
            ->withId('recaptcha-login')
            ->withTheme(RecaptchaV2Theme::Dark)
            ->withType(RecaptchaV2Type::Audio)
            ->withSize(RecaptchaV2Size::Compact)
            ->withResponseFieldName('g-recaptcha-response')
            ->withCallback('onRecaptchaSuccess')
            ->withExpiredCallback('onRecaptchaExpired')
            ->withErrorCallback('onRecaptchaError')
            ->render();
    }
}
