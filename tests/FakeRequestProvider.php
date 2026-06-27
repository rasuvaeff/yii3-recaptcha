<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\RequestProvider\RequestProviderInterface;

/**
 * @internal
 */
final class FakeRequestProvider implements RequestProviderInterface
{
    public int $callCount = 0;

    public function __construct(
        private readonly bool $throw = false,
        private readonly ?ServerRequestInterface $request = null,
    ) {}

    #[\Override]
    public function set(ServerRequestInterface $request): void {}

    #[\Override]
    public function get(): ServerRequestInterface
    {
        $this->callCount++;

        if ($this->throw) {
            throw new \Yiisoft\RequestProvider\RequestNotSetException();
        }

        if ($this->request === null) {
            throw new \LogicException('FakeRequestProvider has no request configured');
        }

        return $this->request;
    }
}
