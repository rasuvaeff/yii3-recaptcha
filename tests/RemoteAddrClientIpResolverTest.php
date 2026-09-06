<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Nyholm\Psr7\ServerRequest;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3Recaptcha\RemoteAddrClientIpResolver;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Yiisoft\RequestProvider\RequestNotSetException;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\RequestProvider\RequestProviderInterface;

use function Rasuvaeff\Understudy\verify;
use function Rasuvaeff\Understudy\when;

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
        $requestProvider = Understudy::for(RequestProviderInterface::class);
        when(fn() => $requestProvider->get())->throws(new RequestNotSetException());

        $resolver = new RemoteAddrClientIpResolver($requestProvider);

        Assert::null($resolver->resolve());
        verify(fn() => $requestProvider->get(), times: 1);
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
