<?php

namespace Tests\Unit\Logging;

use Codeception\Test\Unit;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
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

    public function testChannelWithoutConfigUsesItsContainerEntry(): void
    {
        $billingLogger = new NullLogger();
        $container = new TestContainer([
            'channels.billing' => $billingLogger,
        ]);

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
            'channelConfig' => [],
        ]);

        verify($manager->get('billing'))->same($billingLogger);
    }

    public function testConfigWinsOverTheContainerEntryOfTheChannel(): void
    {
        $configured = new NullLogger();
        $container = new TestContainer([
            'channels.billing' => new NullLogger(),
            'other.logger' => $configured,
        ]);

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
            'channelConfig' => ['billing' => 'other.logger'],
        ]);

        verify($manager->get('billing'))->same($configured);
    }

    public function testUnknownChannelWarnsOnceOnTheDefaultChannel(): void
    {
        $warnings = [];
        $defaultLogger = $this->makeEmpty(LoggerInterface::class, [
            'warning' => function (string $message, array $context = []) use (&$warnings) {
                $warnings[] = [$message, $context];
            },
        ]);

        $manager = $this->make(ChannelManager::class, [
            'container' => new TestContainer(['channels.default' => $defaultLogger]),
            'channelConfig' => ['default' => 'channels.default'],
        ]);

        // A typo in the name of the channel
        $first = $manager->get('biling');
        $second = $manager->get('biling');

        verify($first)->instanceOf(NullLogger::class);
        verify($second)->same($first);
        verify(count($warnings))->equals(1);
        verify($warnings[0][1]['channel'])->equals('biling');
    }

    public function testChannelThatPointsAtAMissingEntryWarns(): void
    {
        $warnings = 0;
        $defaultLogger = $this->makeEmpty(LoggerInterface::class, [
            'warning' => function () use (&$warnings) {
                $warnings++;
            },
        ]);

        $manager = $this->make(ChannelManager::class, [
            'container' => new TestContainer(['channels.default' => $defaultLogger]),
            'channelConfig' => ['default' => 'channels.default', 'audit' => 'channels.does_not_exist'],
        ]);

        verify($manager->get('audit'))->instanceOf(NullLogger::class);
        verify($warnings)->equals(1);
    }

    public function testMissingDefaultChannelDoesNotWarnOrLoop(): void
    {
        $manager = $this->make(ChannelManager::class, [
            'container' => new TestContainer(),
            'channelConfig' => [],
        ]);

        verify($manager->get('default'))->instanceOf(NullLogger::class);
        verify($manager->get('other'))->instanceOf(NullLogger::class);
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
