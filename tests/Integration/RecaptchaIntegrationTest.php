<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests\Integration;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;

#[CoversNothing]
final class RecaptchaIntegrationTest extends TestCase
{
    private RecaptchaClient $client;

    #[\Override]
    protected function setUp(): void
    {
        $secret = $_ENV['RECAPTCHA_SECRET'] ?? null;

        if ($secret === null) {
            $this->markTestSkipped('RECAPTCHA_SECRET env variable is not set');
        }

        $config = new RecaptchaConfig(secretV2: $secret);
        $psr17 = new Psr17Factory();

        $httpClient = new \GuzzleHttp\Client();

        $this->client = new RecaptchaClient(
            config: $config,
            httpClient: $httpClient,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );
    }

    #[Test]
    public function alwaysPassSecretReturnsSuccess(): void
    {
        $result = $this->client->verify(token: 'dummy-token');

        $this->assertTrue($result->success);
    }

    #[Test]
    public function verifyReturnsHostname(): void
    {
        $result = $this->client->verify(token: 'dummy-token');

        $this->assertNotNull($result->hostname);
    }
}
