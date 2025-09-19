<?php

namespace Tests\Unit\Logging;

use Codeception\Test\Unit;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\NullLogger;
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

        self::assertSame($configuredLogger, $logger);
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

        self::assertSame($defaultLogger, $manager->getDefaultLogger());
        self::assertSame($defaultLogger, $manager->get('default'));
    }

    public function testReturnsNullLoggerWhenUnavailable(): void
    {
        $container = new TestContainer();

        $manager = $this->make(ChannelManager::class, [
            'container' => $container,
        ]);

        $logger = $manager->get('missing');

        self::assertInstanceOf(NullLogger::class, $logger);
        self::assertSame($logger, $manager->get('missing'));
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

        self::assertInstanceOf(Logger::class, $logger);
        self::assertSame($logger, $manager->get('test'));

        $handlers = $logger->getHandlers();
        self::assertCount(1, $handlers);
        self::assertInstanceOf(StreamHandler::class, $handlers[0]);
        self::assertSame('/tmp/test.log', $handlers[0]->getUrl());
    }
}

/**
 * @internal
 *
 * @psalm-internal Tests\Unit\Logging
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
