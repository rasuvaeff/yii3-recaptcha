<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests\Integration;

use GuzzleHttp\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[CoversNothing]
final class RecaptchaIntegrationTest
{
    private ?RecaptchaClient $client = null;

    #[BeforeTest]
    public function setUp(): void
    {
        $secret = $_ENV['RECAPTCHA_SECRET'] ?? null;

        if ($secret === null) {
            return;
        }

        $config = new RecaptchaConfig(secretV2: $secret);
        $psr17 = new Psr17Factory();
        $httpClient = new Client();

        $this->client = new RecaptchaClient(
            config: $config,
            httpClient: $httpClient,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );
    }

    public function alwaysPassSecretReturnsSuccess(): void
    {
        if ($this->client === null) {
            return;
        }

        $result = $this->client->verify(token: 'dummy-token');

        Assert::true($result->success);
    }

    public function verifyReturnsHostname(): void
    {
        if ($this->client === null) {
            return;
        }

        $result = $this->client->verify(token: 'dummy-token');

        Assert::notNull($result->hostname);
    }
}
