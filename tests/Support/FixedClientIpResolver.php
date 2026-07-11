<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests\Support;

use Rasuvaeff\Yii3Recaptcha\ClientIpResolverInterface;

/**
 * @internal
 */
final readonly class FixedClientIpResolver implements ClientIpResolverInterface
{
    public function __construct(private ?string $ip) {}

    #[\Override]
    public function resolve(): ?string
    {
        return $this->ip;
    }
}
