<?php

namespace Tests\Unit\Queue;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Tests\Support\StaticArrayJob;
use Tests\Support\StaticArrayJobHandler;
use WebFramework\Database\Database;
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
                $this->makeEmpty(Database::class),
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
                $this->makeEmpty(Database::class),
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
                $this->makeEmpty(Database::class),
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
                $this->makeEmpty(Database::class),
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
                $this->makeEmpty(Database::class),
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
                $this->makeEmpty(Database::class),
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

    /**
     * @param array<string, mixed> $queue
     * @param array<string, mixed> $database
     * @param array<string, mixed> $constructorOverrides
     */
    private function buildWorker(array $queue, mixed $handle, array $database, array $constructorOverrides): QueueWorker
    {
        $memoryQueue = $this->make(MemoryQueue::class, $queue);
        $jobHandler = $this->make(StaticArrayJobHandler::class, ['handle' => $handle]);

        return $this->construct(
            QueueWorker::class,
            [
                $this->makeEmpty(Database::class, $database),
                $this->makeEmpty(QueueService::class, [
                    'get' => fn () => $memoryQueue,
                    'getJobHandler' => fn () => $jobHandler,
                ]),
                $this->makeEmpty(LogService::class),
                'default',
            ],
            $constructorOverrides,
        );
    }

    private function buildJob(): StaticArrayJob
    {
        $job = new StaticArrayJob('value-1');
        $job->setJobId('test-job-id');

        return $job;
    }

    public function testIdleWorkerStopsAtMaxRuntime()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $polls = 0;

        $worker = $this->buildWorker(
            [
                'popJob' => function () use (&$polls) {
                    $polls++;

                    if ($polls > 50)
                    {
                        throw new \LogicException('The worker did not stop at its maximum runtime');
                    }

                    // Never a job: the queue stays empty
                    return null;
                },
            ],
            Expected::never(),
            [],
            ['maxRuntime' => 5, 'emptySleep' => 1],
        );

        $worker->execute();

        verify($polls)->lessThanOrEqual(6);
    }

    public function testFailedJobThatLeftATransactionOpenIsRolledBackBeforeItIsMarkedFailed()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $depth = 2;
        $events = [];

        $worker = $this->buildWorker(
            [
                'popJob' => fn () => $this->buildJob(),
                'markJobFailed' => Expected::once(function () use (&$depth, &$events) {
                    $events[] = "markJobFailed at depth {$depth}";
                }),
                'markJobCompleted' => Expected::never(),
            ],
            function () {
                // Started a transaction, twice nested, and threw before committing
                throw new \RuntimeException('Job failed');
            },
            [
                'getTransactionDepth' => function () use (&$depth) {
                    return $depth;
                },
                'rollbackTransaction' => function () use (&$depth, &$events) {
                    $depth--;
                    $events[] = 'rollback';
                },
            ],
            ['maxJobs' => 1],
        );

        $worker->execute();

        verify($events)->same(['rollback', 'rollback', 'markJobFailed at depth 0']);
    }

    public function testJobThatReturnsWithATransactionOpenIsRolledBackAndFailed()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $depth = 1;
        $rollbacks = 0;

        $worker = $this->buildWorker(
            [
                'popJob' => fn () => $this->buildJob(),
                'markJobCompleted' => Expected::never(),
                'markJobFailed' => Expected::once(function ($job, \Throwable $e) {
                    verify($e->getMessage())->stringContainsString('left a database transaction open');
                }),
            ],
            // Returns normally, but never committed
            fn () => true,
            [
                'getTransactionDepth' => function () use (&$depth) {
                    return $depth;
                },
                'rollbackTransaction' => function () use (&$depth, &$rollbacks) {
                    $depth--;
                    $rollbacks++;
                },
            ],
            ['maxJobs' => 1],
        );

        $worker->execute();

        verify($rollbacks)->equals(1);
    }

    public function testJobWithoutOpenTransactionIsNotRolledBack()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $worker = $this->buildWorker(
            [
                'popJob' => fn () => $this->buildJob(),
                'markJobCompleted' => Expected::once(),
            ],
            fn () => true,
            [
                'getTransactionDepth' => 0,
                'rollbackTransaction' => Expected::never(),
            ],
            ['maxJobs' => 1],
        );

        $worker->execute();
    }
}
