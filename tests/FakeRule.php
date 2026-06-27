<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Yiisoft\Validator\RuleHandlerInterface;
use Yiisoft\Validator\RuleInterface;

/**
 * @internal
 */
final class FakeRule implements RuleInterface
{
    #[\Override]
    public function getHandler(): string|RuleHandlerInterface
    {
        return 'fake-handler';
    }
}
