<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha;

/**
 * Resolves the end-user client IP that is sent to Google as `remoteip` when a
 * rule enables `sendRemoteIp`.
 *
 * The default {@see RemoteAddrClientIpResolver} reads `REMOTE_ADDR`, which is
 * correct only when the application is not behind a proxy/CDN (or when a
 * trusted-hosts middleware has already normalised `REMOTE_ADDR`). Behind a
 * proxy/CDN, bind this interface to an adapter over the application's own
 * client-IP detector so the real client IP is used instead of the proxy IP.
 *
 * @api
 */
interface ClientIpResolverInterface
{
    public function resolve(): ?string;
}
