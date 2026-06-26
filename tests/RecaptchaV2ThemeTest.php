<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Theme;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(RecaptchaV2Theme::class)]
final class RecaptchaV2ThemeTest
{
    #[DataProvider('allCasesProvider')]
    public function allCasesHaveNonEmptyValue(RecaptchaV2Theme $theme): void
    {
        Assert::true($theme->value !== '');
    }

    /**
     * @return iterable<string, array{RecaptchaV2Theme}>
     */
    public static function allCasesProvider(): iterable
    {
        foreach (RecaptchaV2Theme::cases() as $case) {
            yield $case->name => [$case];
        }
    }
}
