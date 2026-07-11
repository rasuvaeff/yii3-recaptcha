<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

use Yiisoft\RequestProvider\RequestNotSetException;
use Yiisoft\RequestProvider\RequestProviderInterface;

/**
 * Default client-IP resolver: reads `REMOTE_ADDR` from the current request.
 *
 * Correct for applications that are not behind a proxy/CDN, or whose
 * trusted-hosts middleware has already rewritten `REMOTE_ADDR` to the real
 * client. Behind an un-normalised proxy this returns the proxy IP — bind a
 * custom {@see ClientIpResolverInterface} in that case.
 *
 * @api
 */
final readonly class RemoteAddrClientIpResolver implements ClientIpResolverInterface
{
    public function __construct(
        private ?RequestProviderInterface $requestProvider = null,
    ) {}

    #[\Override]
    public function resolve(): ?string
    {
        if ($this->requestProvider === null) {
            return null;
        }

        try {
            $serverParams = $this->requestProvider->get()->getServerParams();
        } catch (RequestNotSetException) {
            return null;
        }

        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? null;

        if (!\is_string($remoteAddr) || filter_var($remoteAddr, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $remoteAddr;
    }
}
