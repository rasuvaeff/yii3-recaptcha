<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests\Support;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * @internal
 */
final class ThrowingClientException extends RuntimeException implements ClientExceptionInterface {}
