<?php

namespace Tests\Unit\Queue;

use Carbon\Carbon;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Tests\Support\StaticArrayJob;
use WebFramework\Queue\MemoryQueue;

/**
 * @internal
 *
 * @covers \WebFramework\Queue\MemoryQueue
 */
final class MemoryQueueTest extends Unit
{
    public function testGetName()
    {
        $queue = new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'test-queue');
        verify($queue->getName())->equals('test-queue');
    }

    public function testDispatchImmediateJob()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $logger = $this->makeEmpty(LoggerInterface::class);

        $queue = new MemoryQueue($logger, 'test-queue');
        $job = new StaticArrayJob('test');
        $job->setJobId('job-123');

        $queue->dispatch($job);

        verify($queue->count())->equals(1);
    }

    public function testDispatchDelayedJob()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $logger = $this->makeEmpty(LoggerInterface::class);

        $queue = new MemoryQueue($logger, 'test-queue');
        $job = new StaticArrayJob('test');
        $job->setJobId('job-123');

        $queue->dispatch($job, 60);

        verify($queue->count())->equals(1);
    }

    public function testCountWithMultipleJobs()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $queue = new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'test-queue');

        $job1 = new StaticArrayJob('test1');
        $job1->setJobId('job-1');
        $job2 = new StaticArrayJob('test2');
        $job2->setJobId('job-2');
        $job3 = new StaticArrayJob('test3');
        $job3->setJobId('job-3');

        $queue->dispatch($job1);
        $queue->dispatch($job2, 10);
        $queue->dispatch($job3);

        verify($queue->count())->equals(3);
    }

    public function testPopJobReturnsNullWhenEmpty()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $queue = new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'test-queue');

        verify($queue->popJob())->null();
    }

    public function testPopJobReturnsJobsInFifoOrder()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $queue = new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'test-queue');

        $job1 = new StaticArrayJob('test1');
        $job1->setJobId('job-1');
        $job2 = new StaticArrayJob('test2');
        $job2->setJobId('job-2');
        $job3 = new StaticArrayJob('test3');
        $job3->setJobId('job-3');

        $queue->dispatch($job1);
        $queue->dispatch($job2);
        $queue->dispatch($job3);

        // Should return in FIFO order (first in, first out)
        $popped = $queue->popJob();
        verify($popped)->instanceOf(StaticArrayJob::class);
        verify($popped->name)->equals('test1');

        $popped = $queue->popJob();
        verify($popped->name)->equals('test2');

        $popped = $queue->popJob();
        verify($popped->name)->equals('test3');

        verify($queue->popJob())->null();
    }

    public function testPopJobMovesDelayedJobsWhenTimePasses()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $queue = new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'test-queue');

        $job1 = new StaticArrayJob('immediate');
        $job1->setJobId('job-1');
        $job2 = new StaticArrayJob('delayed');
        $job2->setJobId('job-2');

        $queue->dispatch($job1);
        $queue->dispatch($job2, 60);

        // Pop immediate job
        $popped = $queue->popJob();
        verify($popped->name)->equals('immediate');

        // Delayed job should still be delayed
        verify($queue->count())->equals(1);
        verify($queue->popJob())->null();

        // Move time forward
        Carbon::setTestNow('2025-01-01 12:01:00');

        // Now delayed job should be available
        $popped = $queue->popJob();
        verify($popped)->instanceOf(StaticArrayJob::class);
        verify($popped->name)->equals('delayed');
    }

    public function testPopJobSortsDelayedJobsByTimestamp()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $queue = new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'test-queue');

        $job1 = new StaticArrayJob('delayed-60');
        $job1->setJobId('job-1');
        $job2 = new StaticArrayJob('delayed-30');
        $job2->setJobId('job-2');
        $job3 = new StaticArrayJob('delayed-90');
        $job3->setJobId('job-3');

        $queue->dispatch($job1, 60);
        $queue->dispatch($job2, 30);
        $queue->dispatch($job3, 90);

        // Move time forward to make all jobs available
        Carbon::setTestNow('2025-01-01 12:02:00');

        // Should return jobs sorted by timestamp (earliest first)
        $popped = $queue->popJob();
        verify($popped->name)->equals('delayed-30');

        $popped = $queue->popJob();
        verify($popped->name)->equals('delayed-60');

        $popped = $queue->popJob();
        verify($popped->name)->equals('delayed-90');
    }

    public function testClear()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $logger = $this->makeEmpty(LoggerInterface::class);
        $queue = new MemoryQueue($logger, 'test-queue');

        $job1 = new StaticArrayJob('test1');
        $job1->setJobId('job-1');
        $job2 = new StaticArrayJob('test2');
        $job2->setJobId('job-2');

        $queue->dispatch($job1);
        $queue->dispatch($job2, 10);

        verify($queue->count())->equals(2);

        $queue->clear();

        verify($queue->count())->equals(0);
        verify($queue->popJob())->null();
    }

    public function testPopJobOnlyProcessesDelayedJobsWhenTimeHasPassed()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $queue = new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'test-queue');

        $job1 = new StaticArrayJob('immediate');
        $job1->setJobId('job-1');
        $job2 = new StaticArrayJob('delayed');
        $job2->setJobId('job-2');

        $queue->dispatch($job1);
        $queue->dispatch($job2, 60);

        // Pop immediate job
        $popped = $queue->popJob();
        verify($popped->name)->equals('immediate');

        // Pop again immediately - should not process delayed job yet
        verify($queue->popJob())->null();

        // Move time forward but not enough
        Carbon::setTestNow('2025-01-01 12:00:30');

        // Still should not be available
        verify($queue->popJob())->null();

        // Move time forward enough
        Carbon::setTestNow('2025-01-01 12:01:00');

        // Now should be available
        $popped = $queue->popJob();
        verify($popped->name)->equals('delayed');
    }

    public function testCountIncludesBothImmediateAndDelayedJobs()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $queue = new MemoryQueue($this->makeEmpty(LoggerInterface::class), 'test-queue');

        $job1 = new StaticArrayJob('immediate1');
        $job1->setJobId('job-1');
        $job2 = new StaticArrayJob('immediate2');
        $job2->setJobId('job-2');
        $job3 = new StaticArrayJob('delayed1');
        $job3->setJobId('job-3');
        $job4 = new StaticArrayJob('delayed2');
        $job4->setJobId('job-4');

        $queue->dispatch($job1);
        $queue->dispatch($job2);
        $queue->dispatch($job3, 30);
        $queue->dispatch($job4, 60);

        verify($queue->count())->equals(4);

        // Pop one immediate job
        $queue->popJob();
        verify($queue->count())->equals(3);

        // Move time forward to make delayed jobs available
        Carbon::setTestNow('2025-01-01 12:02:00');

        // Count should still include delayed jobs until they're moved
        verify($queue->count())->equals(3);

        // Pop all jobs
        $queue->popJob();
        $queue->popJob();
        $queue->popJob();

        verify($queue->count())->equals(0);
    }
}
