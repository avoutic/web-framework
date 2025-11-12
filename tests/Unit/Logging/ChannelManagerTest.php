<?php

namespace Tests\Unit\Logging;

use Codeception\Test\Unit;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\NullLogger;
use WebFramework\Core\RuntimeEnvironment;
use WebFramework\Logging\ChannelManager;

/**
 * @internal
 *
 * @covers \WebFramework\Logging\ChannelManager
 */
final class ChannelManagerTest extends Unit
{
    public function testReturnsConfiguredLoggerFromChannelMap(): void
    {
        $configuredLogger = new NullLogger();
        $container = new TestContainer([
            'custom.logger' => $configuredLogger,
        ]);

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
            'channelConfig' => ['payments' => 'custom.logger'],
        ]);

        $logger = $manager->get('payments');

        verify($logger)->equals($configuredLogger);
    }

    public function testFallsBackToNamedService(): void
    {
        $defaultLogger = new NullLogger();
        $container = new TestContainer([
            'channels.default' => $defaultLogger,
        ]);

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
            'channelConfig' => ['default' => 'channels.default'],
        ]);

        verify($manager->getDefaultLogger())->equals($defaultLogger);
        verify($manager->get('default'))->equals($defaultLogger);
    }

    public function testReturnsNullLoggerWhenUnavailable(): void
    {
        $container = new TestContainer();

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
        ]);

        $logger = $manager->get('missing');

        verify($logger)->instanceOf(NullLogger::class);
        verify($manager->get('missing'))->equals($logger);
    }

    public function testReturnsFileLogger(): void
    {
        $container = new TestContainer();

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
            'channelConfig' => ['test' => [
                'type' => 'file',
                'path' => '/tmp/test.log',
            ]],
        ]);

        $logger = $manager->get('test');

        verify($logger)->instanceOf(Logger::class);
        verify($manager->get('test'))->equals($logger);

        $handlers = $logger->getHandlers();
        verify(count($handlers))->equals(1);
        verify($handlers[0])->instanceOf(StreamHandler::class);
        verify($handlers[0]->getUrl())->equals('/tmp/test.log');
    }

    public function testReturnsFileLoggerWithRelativePath(): void
    {
        $container = new TestContainer();
        $appDir = '/app/directory';

        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getAppDir' => $appDir,
        ]);

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
            'runtimeEnvironment' => $runtimeEnvironment,
            'channelConfig' => ['test' => [
                'type' => 'file',
                'path' => 'logs/test.log',
            ]],
        ]);

        $logger = $manager->get('test');

        verify($logger)->instanceOf(Logger::class);
        verify($manager->get('test'))->equals($logger);

        $handlers = $logger->getHandlers();
        verify(count($handlers))->equals(1);
        verify($handlers[0])->instanceOf(StreamHandler::class);
        verify($handlers[0]->getUrl())->equals($appDir.'/logs/test.log');
    }

    public function testReturnsFileLoggerWithAbsolutePathUnchanged(): void
    {
        $container = new TestContainer();
        $appDir = '/app/directory';

        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getAppDir' => $appDir,
        ]);

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
            'runtimeEnvironment' => $runtimeEnvironment,
            'channelConfig' => ['test' => [
                'type' => 'file',
                'path' => '/absolute/path/test.log',
            ]],
        ]);

        $logger = $manager->get('test');

        verify($logger)->instanceOf(Logger::class);

        $handlers = $logger->getHandlers();
        verify(count($handlers))->equals(1);
        verify($handlers[0])->instanceOf(StreamHandler::class);
        verify($handlers[0]->getUrl())->equals('/absolute/path/test.log');
    }

    public function testThrowsExceptionWhenTypeIsMissing(): void
    {
        $container = new TestContainer();

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
            'channelConfig' => ['test' => [
                'path' => '/tmp/test.log',
            ]],
        ]);

        verify(function () use ($manager) {
            $manager->get('test');
        })->callableThrows(\InvalidArgumentException::class, 'Channel configuration for test is missing a type');
    }

    public function testThrowsExceptionWhenPathIsMissingForFileType(): void
    {
        $container = new TestContainer();

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
            'channelConfig' => ['test' => [
                'type' => 'file',
            ]],
        ]);

        verify(function () use ($manager) {
            $manager->get('test');
        })->callableThrows(\InvalidArgumentException::class, 'Channel configuration for test is missing a path');
    }

    public function testThrowsExceptionForUnknownChannelType(): void
    {
        $container = new TestContainer();

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
            'channelConfig' => ['test' => [
                'type' => 'unknown_type',
                'path' => '/tmp/test.log',
            ]],
        ]);

        verify(function () use ($manager) {
            $manager->get('test');
        })->callableThrows(\InvalidArgumentException::class, 'Unknown channel type unknown_type for test');
    }
}

/**
 * @internal
 */
final class TestContainer implements ContainerInterface
{
    /**
     * @param array<string, mixed> $services
     */
    public function __construct(private array $services = []) {}

    public function get(string $id): mixed
    {
        if (!$this->has($id))
        {
            throw new class(sprintf('Service %s not found', $id)) extends \RuntimeException implements NotFoundExceptionInterface {};
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }
}
