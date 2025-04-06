<?php

namespace Tests\Unit\Queue;

use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use Tests\Support\StaticArrayJob;
use WebFramework\Queue\MemoryQueue;
use WebFramework\Queue\QueueService;

/**
 * @internal
 *
 * @coversNothing
 */
final class DispatchTest extends Unit
{
    public function testDispatchJob()
    {
        $instance = $this->construct(
            QueueService::class,
            [
                $this->makeEmpty(Container::class),
            ]
        );

        $instance->register('testQueue', new MemoryQueue('test'));

        verify($instance->count('testQueue'))
            ->equals(0)
        ;

        $instance->dispatch(new StaticArrayJob('value-1'), 'testQueue');

        verify($instance->count('testQueue'))
            ->equals(1)
        ;
    }

    public function testDispatchJobToNonExistentDefault()
    {
        $instance = $this->construct(
            QueueService::class,
            [
                $this->makeEmpty(Container::class),
            ]
        );

        $instance->register('testQueue', new MemoryQueue('test'));

        verify($instance->count('testQueue'))
            ->equals(0)
        ;

        verify(function () use ($instance) {
            $instance->dispatch(new StaticArrayJob('value-1'));
        })
            ->callableThrows(\RuntimeException::class, 'Default queue is not set')
        ;

        verify($instance->count('testQueue'))
            ->equals(0)
        ;
    }

    public function testDispatchMultipleJobs()
    {
        $instance = $this->construct(
            QueueService::class,
            [
                $this->makeEmpty(Container::class),
            ]
        );

        $instance->register('testQueue', new MemoryQueue('test'));

        $instance->dispatch(new StaticArrayJob('value-1'), 'testQueue');
        $instance->dispatch(new StaticArrayJob('value-2'), 'testQueue');

        verify($instance->count('testQueue'))
            ->equals(2)
        ;
    }
}
