<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2Type;

#[CoversClass(RecaptchaV2Type::class)]
final class RecaptchaV2TypeTest extends TestCase
{
    /**
     * @return iterable<string, array{RecaptchaV2Type}>
     */
    public static function allCasesProvider(): iterable
    {
        foreach (RecaptchaV2Type::cases() as $case) {
            yield $case->name => [$case];
        }
    }

    #[DataProvider('allCasesProvider')]
    #[Test]
    public function allCasesHaveNonEmptyValue(RecaptchaV2Type $type): void
    {
        $this->assertNotEmpty($type->value);
    }
}
