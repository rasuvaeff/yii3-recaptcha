<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3Recaptcha\RecaptchaClient;
use Rasuvaeff\Yii3Recaptcha\RecaptchaConfig;

// Headless flow: an SPA/Next.js frontend obtained the token and POSTed it to the
// API. The backend only verifies it — no widget rendering. This example stubs the
// PSR-18 client with a canned siteverify response so it runs without network/keys
// (real automated tests should mock the HTTP layer the same way).
$stubHttpClient = new class implements ClientInterface {
    #[\Override]
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return new Response(200, [], '{"success":true,"score":0.9,"action":"login","hostname":"example.com"}');
    }
};

$psr17 = new Psr17Factory();
$client = new RecaptchaClient(
    config: new RecaptchaConfig(secretV3: 'server-side-secret'),
    httpClient: $stubHttpClient,
    requestFactory: $psr17,
    streamFactory: $psr17,
);

$result = $client->verifyV3(token: 'token-from-frontend');

echo "=== headless v3 verification ===\n";
echo 'success: ' . ($result->success ? 'true' : 'false') . "\n";
echo 'score:   ' . (string) $result->score . "\n";
echo 'action:  ' . (string) $result->action . "\n";

// Graduated decision on the raw score (instead of a fixed-threshold rule):
$decision = match (true) {
    !$result->success, $result->isTransportError() => 'deny',
    $result->score !== null && $result->score >= 0.7 => 'allow',
    $result->score !== null && $result->score >= 0.3 => 'challenge',
    default => 'deny',
};

echo 'decision: ' . $decision . "\n";
