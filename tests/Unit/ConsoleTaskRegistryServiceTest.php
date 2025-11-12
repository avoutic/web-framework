<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use Tests\Support\TestConsoleTask;
use WebFramework\Config\ConfigService;
use WebFramework\Console\ConsoleTaskRegistryService;

/**
 * @internal
 *
 * @covers \WebFramework\Console\ConsoleTaskRegistryService
 */
final class ConsoleTaskRegistryServiceTest extends Unit
{
    public function testDiscoverAppTasksEmptyConfiguration()
    {
        $taskDiscovery = $this->make(ConsoleTaskRegistryService::class, [
            'configService' => $this->makeEmpty(ConfigService::class, [
                'get' => [
                ],
            ]),
        ]);

        $tasks = $taskDiscovery->getAppTasks();

        verify($tasks)->empty();
    }

    public function testDiscoverAppTasksWithValidConfiguration()
    {
        $taskDiscovery = $this->make(ConsoleTaskRegistryService::class, [
            'configService' => $this->makeEmpty(ConfigService::class, [
                'get' => [
                    'test:console' => TestConsoleTask::class,
                ],
            ]),
            'container' => $this->makeEmpty(Container::class, [
                'get' => $this->makeEmpty(TestConsoleTask::class),
            ]),
        ]);

        $tasks = $taskDiscovery->getAppTasks();

        verify($tasks)->equals([
            'test:console' => TestConsoleTask::class,
        ]);

        $task = $taskDiscovery->getTaskForCommand('test:console');

        verify($task)->instanceOf(TestConsoleTask::class);
    }

    public function testDiscoverAppTasksWithInvalidConfiguration()
    {
        $taskDiscovery = $this->make(ConsoleTaskRegistryService::class, [
            'configService' => $this->makeEmpty(ConfigService::class, [
                'get' => [
                    'test:console' => 'NonExistentTaskClass',
                ],
            ]),
            'container' => $this->makeEmpty(Container::class, [
                'get' => null,
            ]),
        ]);

        $tasks = $taskDiscovery->getAppTasks();

        verify(function () use ($taskDiscovery) {
            $task = $taskDiscovery->getTaskForCommand('test:console');
        })->callableThrows(\RuntimeException::class);
    }
}
