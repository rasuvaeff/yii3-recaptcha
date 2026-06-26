<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Size;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(RecaptchaV2Size::class)]
final class RecaptchaV2SizeTest
{
    #[DataProvider('allCasesProvider')]
    public function allCasesHaveNonEmptyValue(RecaptchaV2Size $size): void
    {
        Assert::true($size->value !== '');
    }

    /**
     * @return iterable<string, array{RecaptchaV2Size}>
     */
    public static function allCasesProvider(): iterable
    {
        foreach (RecaptchaV2Size::cases() as $case) {
            yield $case->name => [$case];
        }
    }
}
