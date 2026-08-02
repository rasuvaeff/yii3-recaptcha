<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Nyholm\Psr7\ServerRequest;
use Rasuvaeff\Yii3Recaptcha\RemoteAddrClientIpResolver;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Yiisoft\RequestProvider\RequestProvider;

#[Test]
#[Covers(RemoteAddrClientIpResolver::class)]
final class RemoteAddrClientIpResolverTest
{
    public function noRequestProviderReturnsNull(): void
    {
        $resolver = new RemoteAddrClientIpResolver();

        Assert::null($resolver->resolve());
    }

    public function requestNotSetExceptionIsCaughtAndReturnsNull(): void
    {
        $requestProvider = new FakeRequestProvider(throw: true);
        $resolver = new RemoteAddrClientIpResolver($requestProvider);

        Assert::null($resolver->resolve());
        Assert::same($requestProvider->callCount, 1);
    }

    public function validRemoteAddrIsReturned(): void
    {
        $requestProvider = new RequestProvider(
            new ServerRequest('GET', '/', serverParams: ['REMOTE_ADDR' => '203.0.113.5']),
        );
        $resolver = new RemoteAddrClientIpResolver($requestProvider);

        Assert::same($resolver->resolve(), '203.0.113.5');
    }

    public function validIpv6RemoteAddrIsReturned(): void
    {
        $requestProvider = new RequestProvider(
            new ServerRequest('GET', '/', serverParams: ['REMOTE_ADDR' => '::1']),
        );
        $resolver = new RemoteAddrClientIpResolver($requestProvider);

        Assert::same($resolver->resolve(), '::1');
    }

    public function missingRemoteAddrReturnsNull(): void
    {
        $requestProvider = new RequestProvider(
            new ServerRequest('GET', '/', serverParams: []),
        );
        $resolver = new RemoteAddrClientIpResolver($requestProvider);

        Assert::null($resolver->resolve());
    }

    public function nonStringRemoteAddrReturnsNull(): void
    {
        $requestProvider = new RequestProvider(
            new ServerRequest('GET', '/', serverParams: ['REMOTE_ADDR' => 12345]),
        );
        $resolver = new RemoteAddrClientIpResolver($requestProvider);

        Assert::null($resolver->resolve());
    }

    public function invalidIpFormatReturnsNull(): void
    {
        $requestProvider = new RequestProvider(
            new ServerRequest('GET', '/', serverParams: ['REMOTE_ADDR' => 'not-an-ip']),
        );
        $resolver = new RemoteAddrClientIpResolver($requestProvider);

        Assert::null($resolver->resolve());
    }
}
