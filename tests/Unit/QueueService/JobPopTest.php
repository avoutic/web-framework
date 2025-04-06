<?php

namespace Tests\Unit\Queue;

use Carbon\Carbon;
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
final class JobPopTest extends Unit
{
    public function testPopJobOrder()
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
        $instance->dispatch(new StaticArrayJob('value-3'), 'testQueue');

        $job = $instance->popJob('testQueue');
        verify($job->name)
            ->equals('value-1')
        ;

        verify($instance->count('testQueue'))
            ->equals(2)
        ;

        $job = $instance->popJob('testQueue');
        verify($job->name)
            ->equals('value-2')
        ;

        verify($instance->count('testQueue'))
            ->equals(1)
        ;

        $job = $instance->popJob('testQueue');
        verify($job->name)
            ->equals('value-3')
        ;

        verify($instance->count('testQueue'))
            ->equals(0)
        ;
    }

    public function testDelayedPop()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $instance = $this->construct(
            QueueService::class,
            [
                $this->makeEmpty(Container::class),
            ]
        );

        $instance->register('testQueue', new MemoryQueue('test'));

        $instance->dispatch(new StaticArrayJob('value-1'), 'testQueue', 10);

        $job = $instance->popJob('testQueue');
        verify($job)
            ->equals(null)
        ;

        verify($instance->count('testQueue'))
            ->equals(1)
        ;

        Carbon::setTestNow('2025-01-01 00:00:09');

        $job = $instance->popJob('testQueue');
        verify($job)
            ->equals(null)
        ;

        verify($instance->count('testQueue'))
            ->equals(1)
        ;

        Carbon::setTestNow('2025-01-01 00:00:10');

        $job = $instance->popJob('testQueue');
        verify($job->name)
            ->equals('value-1')
        ;

        verify($instance->count('testQueue'))
            ->equals(0)
        ;
    }

    public function testDelayedPopOrder()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $instance = $this->construct(
            QueueService::class,
            [
                $this->makeEmpty(Container::class),
            ]
        );

        $instance->register('testQueue', new MemoryQueue('test'));

        $instance->dispatch(new StaticArrayJob('value-1'), 'testQueue', 20);
        $instance->dispatch(new StaticArrayJob('value-2'), 'testQueue', 10);
        $instance->dispatch(new StaticArrayJob('value-3'), 'testQueue', 15);

        $job = $instance->popJob('testQueue');
        verify($job)
            ->equals(null)
        ;

        Carbon::setTestNow('2025-01-01 00:00:20');

        $job = $instance->popJob('testQueue');
        verify($job->name)
            ->equals('value-2')
        ;

        $job = $instance->popJob('testQueue');
        verify($job->name)
            ->equals('value-3')
        ;

        $job = $instance->popJob('testQueue');
        verify($job->name)
            ->equals('value-1')
        ;
    }
}
