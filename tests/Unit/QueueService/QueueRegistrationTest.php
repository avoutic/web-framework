<?php

namespace Tests\Unit\Queue;

use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use Psr\Log\LoggerInterface;
use WebFramework\Queue\MemoryQueue;
use WebFramework\Queue\QueueService;
use WebFramework\Support\UuidProvider;

/**
 * @internal
 *
 * @covers \WebFramework\Queue\QueueService
 */
final class QueueRegistrationTest extends Unit
{
    public function testRegisterQueue()
    {
        $instance = $this->construct(
            QueueService::class,
            [
                $this->makeEmpty(Container::class),
                $this->makeEmpty(LoggerInterface::class),
                $this->makeEmpty(UuidProvider::class),
            ]
        );

        verify($instance->getQueueNames())
            ->equals([])
        ;

        $instance->register('testQueue', new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'testQueue'));

        verify($instance->getQueueNames())
            ->equals(['testQueue'])
        ;
    }

    public function testRegisterDoubleQueue()
    {
        $instance = $this->construct(
            QueueService::class,
            [
                $this->makeEmpty(Container::class),
                $this->makeEmpty(LoggerInterface::class),
                $this->makeEmpty(UuidProvider::class),
            ]
        );

        $instance->register('testQueue', new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'testQueue'));

        verify(function () use ($instance) {
            $instance->register('testQueue', new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'testQueue'));
        })
            ->callableThrows(\RuntimeException::class, "Queue 'testQueue' is already registered")
        ;

        verify($instance->getQueueNames())
            ->equals(['testQueue'])
        ;
    }

    public function testRegisterInvalidQueueName()
    {
        $instance = $this->construct(
            QueueService::class,
            [
                $this->makeEmpty(Container::class),
                $this->makeEmpty(LoggerInterface::class),
                $this->makeEmpty(UuidProvider::class),
            ]
        );

        verify(function () use ($instance) {
            $instance->register('default', new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'default'));
        })
            ->callableThrows(\RuntimeException::class, "Queue 'default' is reserved")
        ;

        verify($instance->getQueueNames())
            ->equals([])
        ;
    }
}
