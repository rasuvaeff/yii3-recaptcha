<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Size;

#[CoversClass(RecaptchaV2Size::class)]
final class RecaptchaV2SizeTest extends TestCase
{
    /**
     * @return iterable<string, array{RecaptchaV2Size}>
     */
    public static function allCasesProvider(): iterable
    {
        foreach (RecaptchaV2Size::cases() as $case) {
            yield $case->name => [$case];
        }
    }

    #[DataProvider('allCasesProvider')]
    #[Test]
    public function allCasesHaveNonEmptyValue(RecaptchaV2Size $size): void
    {
        $this->assertNotEmpty($size->value);
    }
}
