<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Rasuvaeff\Yii3Recaptcha\ClientIpResolverInterface;
use Rasuvaeff\Yii3Recaptcha\RecaptchaRegistry;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV2RuleHandler;
use Rasuvaeff\Yii3Recaptcha\RecaptchaV3RuleHandler;
use Rasuvaeff\Yii3Recaptcha\RemoteAddrClientIpResolver;
use ReflectionClass;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

/**
 * Resolves the package's config/di.php through a real Yiisoft container. That
 * file is not covered by cs/psalm/testo, so a wiring mistake (e.g. a broken
 * definition) is only caught here — not by a hand-called closure.
 */
#[Test]
#[CoversNothing]
final class ConfigWiringTest
{
    public function handlersResolveWithoutRegistryOrOptionalDeps(): void
    {
        $container = $this->buildContainer();

        Assert::instanceOf($container->get(RecaptchaV2RuleHandler::class), RecaptchaV2RuleHandler::class);
        Assert::instanceOf($container->get(RecaptchaV3RuleHandler::class), RecaptchaV3RuleHandler::class);
    }

    public function clientIpResolverBindsToRemoteAddrByDefault(): void
    {
        $container = $this->buildContainer();

        Assert::instanceOf($container->get(ClientIpResolverInterface::class), RemoteAddrClientIpResolver::class);
    }

    public function bootstrapPopulatesRegistryFromContainer(): void
    {
        $registry = new ReflectionClass(RecaptchaRegistry::class);
        foreach (['client', 'ipResolver', 'translator'] as $prop) {
            $registry->getProperty($prop)->setValue(null, null);
        }

        $container = $this->buildContainer();

        /** @var array<callable(Container): void> $bootstrap */
        $bootstrap = require dirname(__DIR__) . '/config/bootstrap.php';
        foreach ($bootstrap as $callable) {
            $callable($container);
        }

        Assert::notNull(RecaptchaRegistry::client());
        Assert::instanceOf(RecaptchaRegistry::ipResolver(), RemoteAddrClientIpResolver::class);

        foreach (['client', 'ipResolver', 'translator'] as $prop) {
            $registry->getProperty($prop)->setValue(null, null);
        }
    }

    private function buildContainer(): Container
    {
        /** @var array $params */
        $params = require dirname(__DIR__) . '/config/params.php';

        /** @var array<string, mixed> $di */
        $di = (static function () use ($params): array {
            return require dirname(__DIR__) . '/config/di.php';
        })();

        $psr17 = new Psr17Factory();

        $definitions = array_merge($di, [
            ClientInterface::class => new FakeHttpClient(),
            RequestFactoryInterface::class => $psr17,
            StreamFactoryInterface::class => $psr17,
        ]);

        return new Container(ContainerConfig::create()->withDefinitions($definitions));
    }
}
