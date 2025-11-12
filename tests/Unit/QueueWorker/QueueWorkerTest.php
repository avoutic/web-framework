<?php

namespace Tests\Unit\Queue;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Tests\Support\StaticArrayJob;
use Tests\Support\StaticArrayJobHandler;
use WebFramework\Exception\ArgumentParserException;
use WebFramework\Logging\LogService;
use WebFramework\Queue\Job;
use WebFramework\Queue\MemoryQueue;
use WebFramework\Queue\QueueService;
use WebFramework\Task\QueueWorker;

/**
 * @internal
 *
 * @covers \WebFramework\Task\QueueWorker
 */
final class QueueWorkerTest extends Unit
{
    public function testSetMaxJobsWithValidNumber()
    {
        $worker = $this->make(QueueWorker::class);
        $worker->setMaxJobs('100');
        verify($worker->getMaxJobs())->equals(100);
    }

    public function testSetMaxJobsWithInvalidNumber()
    {
        $worker = $this->make(QueueWorker::class);
        verify(function () use ($worker) {
            $worker->setMaxJobs('invalid');
        })->callableThrows(ArgumentParserException::class, 'Max jobs must be a number greater than 0');
    }

    public function testSetMaxJobsWithNegativeNumber()
    {
        $worker = $this->make(QueueWorker::class);
        verify(function () use ($worker) {
            $worker->setMaxJobs('-1');
        })->callableThrows(ArgumentParserException::class, 'Max jobs must be a number greater than 0');
    }

    public function testSetMaxRuntimeWithValidNumber()
    {
        $worker = $this->make(QueueWorker::class);
        $worker->setMaxRuntime('3600');
        verify($worker->getMaxRuntime())->equals(3600);
    }

    public function testSetMaxRuntimeWithInvalidNumber()
    {
        $worker = $this->make(QueueWorker::class);
        verify(function () use ($worker) {
            $worker->setMaxRuntime('invalid');
        })->callableThrows(ArgumentParserException::class, 'Max runtime must be a number greater than 0');
    }

    public function testSetMaxRuntimeWithNegativeNumber()
    {
        $worker = $this->make(QueueWorker::class);
        verify(function () use ($worker) {
            $worker->setMaxRuntime('-1');
        })->callableThrows(ArgumentParserException::class, 'Max runtime must be a number greater than 0');
    }

    public function testSetQueueName()
    {
        $worker = $this->make(QueueWorker::class);
        $worker->setQueueName('test-queue');
        verify($worker->getQueueName())->equals('test-queue');
    }

    public function testExecuteThrowsExceptionWhenQueueNameNotSet()
    {
        $worker = $this->make(QueueWorker::class, [
            'queueName' => null,
        ]);
        verify(function () use ($worker) {
            $worker->execute();
        })->callableThrows(ArgumentParserException::class, 'Queue name not set');
    }

    public function testExecuteOneJob()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $queue = $this->make(MemoryQueue::class, [
            'popJob' => function () {
                return new StaticArrayJob('value-1');
            },
        ]);

        $jobHandler = $this->make(StaticArrayJobHandler::class, [
            'handle' => Expected::once(),
        ]);

        $instance = $this->construct(
            QueueWorker::class,
            [
                $this->makeEmpty(QueueService::class, [
                    'get' => function (string $queueName) use ($queue) {
                        if ($queueName === 'default')
                        {
                            return $queue;
                        }

                        return null;
                    },
                    'getJobHandler' => function (Job $job) use ($jobHandler) {
                        return $jobHandler;
                    },
                ]),
                $this->makeEmpty(LogService::class),
                'default',
            ],
            [
                'maxJobs' => 1,
            ]
        );

        $instance->execute();
    }

    public function testExecuteMultipleJobs()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $queue = $this->make(MemoryQueue::class, [
            'popJob' => function () {
                return new StaticArrayJob('value-1');
            },
        ]);

        $jobHandler = $this->make(StaticArrayJobHandler::class, [
            'handle' => Expected::exactly(10),
        ]);

        $instance = $this->construct(
            QueueWorker::class,
            [
                $this->makeEmpty(QueueService::class, [
                    'get' => function (string $queueName) use ($queue) {
                        if ($queueName === 'default')
                        {
                            return $queue;
                        }
                    },
                    'getJobHandler' => function (Job $job) use ($jobHandler) {
                        return $jobHandler;
                    },
                ]),
                $this->makeEmpty(LogService::class),
                'default',
            ],
            [
                'maxJobs' => 10,
            ]
        );

        $instance->execute();
    }

    public function testExecuteWithJobFailure()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $job = new StaticArrayJob('value-1');
        $job->setJobId('test-job-id');

        $queue = $this->make(MemoryQueue::class, [
            'popJob' => function () use ($job) {
                return $job;
            },
            'markJobFailed' => Expected::once(function ($failedJob, $exception) use ($job) {
                verify($failedJob)->equals($job);
                verify($exception)->instanceOf(\Exception::class);
            }),
        ]);

        $jobHandler = $this->make(StaticArrayJobHandler::class, [
            'handle' => Expected::once(function () {
                throw new \Exception('Job failed');
            }),
        ]);

        $logger = $this->makeEmpty(LogService::class, [
            'error' => Expected::exactly(2),
        ]);

        $instance = $this->construct(
            QueueWorker::class,
            [
                $this->makeEmpty(QueueService::class, [
                    'get' => function (string $queueName) use ($queue) {
                        return $queue;
                    },
                    'getJobHandler' => function (Job $job) use ($jobHandler) {
                        return $jobHandler;
                    },
                ]),
                $logger,
                'default',
            ],
            [
                'maxJobs' => 1,
            ]
        );

        $instance->execute();
    }

    public function testExecuteWithMaxRuntime()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $callCount = 0;
        $queue = $this->make(MemoryQueue::class, [
            'popJob' => function () use (&$callCount) {
                $callCount++;
                if ($callCount === 1)
                {
                    // Advance time after first job to trigger max runtime
                    Carbon::setTestNow('2025-01-01 00:00:02');
                }

                return new StaticArrayJob('value-1');
            },
        ]);

        $jobHandler = $this->make(StaticArrayJobHandler::class, [
            'handle' => Expected::atLeastOnce(),
        ]);

        $instance = $this->construct(
            QueueWorker::class,
            [
                $this->makeEmpty(QueueService::class, [
                    'get' => function (string $queueName) use ($queue) {
                        return $queue;
                    },
                    'getJobHandler' => function (Job $job) use ($jobHandler) {
                        return $jobHandler;
                    },
                ]),
                $this->makeEmpty(LogService::class),
                'default',
            ],
            [
                'maxRuntime' => 2,
            ]
        );

        $instance->execute();
    }

    public function testExecuteMarksJobCompleted()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $job = new StaticArrayJob('value-1');

        $queue = $this->make(MemoryQueue::class, [
            'popJob' => function () use ($job) {
                return $job;
            },
            'markJobCompleted' => Expected::once(function ($completedJob) use ($job) {
                verify($completedJob)->equals($job);
            }),
        ]);

        $jobHandler = $this->make(StaticArrayJobHandler::class, [
            'handle' => Expected::once(),
        ]);

        $instance = $this->construct(
            QueueWorker::class,
            [
                $this->makeEmpty(QueueService::class, [
                    'get' => function (string $queueName) use ($queue) {
                        return $queue;
                    },
                    'getJobHandler' => function (Job $job) use ($jobHandler) {
                        return $jobHandler;
                    },
                ]),
                $this->makeEmpty(LogService::class),
                'default',
            ],
            [
                'maxJobs' => 1,
            ]
        );

        $instance->execute();
    }

    public function testExecuteWithNoJobs()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $popCallCount = 0;
        $queue = $this->make(MemoryQueue::class, [
            'popJob' => function () use (&$popCallCount) {
                $popCallCount++;
                if ($popCallCount === 1)
                {
                    // First call returns null (no jobs)
                    return null;
                }

                // Second call returns a job to allow the test to complete
                return new StaticArrayJob('value-1');
            },
        ]);

        $jobHandler = $this->make(StaticArrayJobHandler::class, [
            'handle' => Expected::once(),
        ]);

        $instance = $this->construct(
            QueueWorker::class,
            [
                $this->makeEmpty(QueueService::class, [
                    'get' => function (string $queueName) use ($queue) {
                        return $queue;
                    },
                    'getJobHandler' => function (Job $job) use ($jobHandler) {
                        return $jobHandler;
                    },
                ]),
                $this->makeEmpty(LogService::class),
                'default',
            ],
            [
                'maxJobs' => 1,
            ]
        );

        $instance->execute();
    }
}
