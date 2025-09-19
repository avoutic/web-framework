<?php

namespace Tests\Unit\Logging;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WebFramework\Logging\ChannelManager;
use WebFramework\Logging\LogService;

/**
 * @internal
 *
 * @covers \WebFramework\Logging\LogService
 */
final class LogServiceTest extends Unit
{
    public function testInfoDelegatesToChannelLogger(): void
    {
        $logger = $this->makeEmpty(
            LoggerInterface::class,
            [
                'log' => Expected::once(
                    static function (string $level, string $message, array $context): void {
                        self::assertSame('info', $level);
                        self::assertSame('Received payment', $message);
                        self::assertSame(['amount' => 42], $context);
                    },
                ),
            ],
        );

        $manager = $this->makeEmpty(
            ChannelManager::class,
            [
                'get' => $logger,
            ],
        );

        $service = new LogService($manager);

        $service->info('purchases', 'Received payment', ['amount' => 42]);
    }
}
