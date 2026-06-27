<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Closure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
final class FakeHttpClient implements ClientInterface
{
    private ?Closure $callback = null;

    private ResponseInterface $response;

    public function withCallback(?Closure $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function withResponse(ResponseInterface $response): self
    {
        $this->response = $response;

        return $this;
    }

    #[\Override]
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->callback !== null) {
            return ($this->callback)($request);
        }

        return $this->response;
    }
}
