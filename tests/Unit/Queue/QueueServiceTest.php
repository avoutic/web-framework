<?php

namespace Tests\Unit\Queue;

use Carbon\Carbon;
use Codeception\Test\Unit;
use Tests\Support\StaticArrayJob;
use WebFramework\Queue\MemoryQueue;
use WebFramework\Queue\QueueService;

/**
 * @internal
 *
 * @coversNothing
 */
final class QueueServiceTest extends Unit
{
    public function testRegisterQueue()
    {
        $instance = $this->construct(
            QueueService::class,
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

    public function testDispatchJob()
    {
        $instance = $this->construct(
            QueueService::class,
        );

        $instance->register('testQueue', new MemoryQueue('test'));

        verify($instance->count('testQueue'))
            ->equals(0)
        ;

        verify(StaticArrayJob::$data)
            ->equals([])
        ;

        $instance->dispatch(new StaticArrayJob('value-1'), 'testQueue');

        verify($instance->count('testQueue'))
            ->equals(1)
        ;

        verify(StaticArrayJob::$data)
            ->equals([])
        ;
    }

    public function testDispatchJobToNonExistentDefault()
    {
        $instance = $this->construct(
            QueueService::class,
        );

        $instance->register('testQueue', new MemoryQueue('test'));

        verify($instance->count('testQueue'))
            ->equals(0)
        ;

        verify(StaticArrayJob::$data)
            ->equals([])
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
        );

        $instance->register('testQueue', new MemoryQueue('test'));

        $instance->dispatch(new StaticArrayJob('value-1'), 'testQueue');
        $instance->dispatch(new StaticArrayJob('value-2'), 'testQueue');

        verify($instance->count('testQueue'))
            ->equals(2)
        ;

        verify(StaticArrayJob::$data)
            ->equals([])
        ;
    }

    public function testPopJobOrder()
    {
        $instance = $this->construct(
            QueueService::class,
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

        verify(StaticArrayJob::$data)
            ->equals([])
        ;
    }

    public function testDelayedPop()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $instance = $this->construct(
            QueueService::class,
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
