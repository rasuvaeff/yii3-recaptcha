<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Type;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(RecaptchaV2Type::class)]
final class RecaptchaV2TypeTest
{
    #[DataProvider('allCasesProvider')]
    public function allCasesHaveNonEmptyValue(RecaptchaV2Type $type): void
    {
        Assert::true($type->value !== '');
    }

    /**
     * @return iterable<string, array{RecaptchaV2Type}>
     */
    public static function allCasesProvider(): iterable
    {
        foreach (RecaptchaV2Type::cases() as $case) {
            yield $case->name => [$case];
        }
    }
}
