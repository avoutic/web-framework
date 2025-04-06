<?php

namespace Tests\Unit\Queue;

use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use WebFramework\Queue\MemoryQueue;
use WebFramework\Queue\QueueService;

/**
 * @internal
 *
 * @coversNothing
 */
final class QueueRegistrationTest extends Unit
{
    public function testRegisterQueue()
    {
        $instance = $this->construct(
            QueueService::class,
            [
                $this->makeEmpty(Container::class),
            ]
        );

        verify($instance->getQueueNames())
            ->equals([])
        ;

        $instance->register('testQueue', new MemoryQueue('testQueue'));

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
            ]
        );

        $instance->register('testQueue', new MemoryQueue('testQueue'));

        verify(function () use ($instance) {
            $instance->register('testQueue', new MemoryQueue('testQueue'));
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
            ]
        );

        verify(function () use ($instance) {
            $instance->register('default', new MemoryQueue('default'));
        })
            ->callableThrows(\RuntimeException::class, "Queue 'default' is reserved")
        ;

        verify($instance->getQueueNames())
            ->equals([])
        ;
    }
}
