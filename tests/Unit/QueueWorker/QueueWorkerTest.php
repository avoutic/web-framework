<?php

namespace Tests\Unit\Queue;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Tests\Support\StaticArrayJob;
use Tests\Support\StaticArrayJobHandler;
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
}
