<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Task;

use Carbon\Carbon;
use WebFramework\Queue\QueueService;

class QueueWorker extends ConsoleTask
{
    private ?int $maxJobs = null;
    private ?int $maxRuntime = null;

    public function __construct(
        private QueueService $queueService,
        private ?string $queueName = null,
    ) {}

    public function getCommand(): string
    {
        return 'queue:worker';
    }

    public function getDescription(): string
    {
        return 'Run a queue worker';
    }

    public function getUsage(): string
    {
        return <<<'EOF'
        Run a queue worker.

        This task will run a queue worker for the given queue name.

        The worker will process jobs from the queue until the maximum number of jobs or
        runtime is reached.

        The worker will sleep for 1 second, and check again for jobs, if there are no
        jobs to process.

        Usage:
        framework queue:worker <queueName> [--max-jobs=<maxJobs>] [--max-runtime=<maxRuntime>]

        Options:
        --max-jobs=<maxJobs>    The maximum number of jobs to process (default: unlimited)
        --max-runtime=<maxRuntime> The maximum run time of the worker (default: unlimited)
        EOF;
    }

    public function getArguments(): array
    {
        return [
            [
                'name' => 'queueName',
                'description' => 'The name of the queue to work',
                'required' => true,
                'setter' => [$this, 'setQueueName'],
            ],
        ];
    }

    public function getOptions(): array
    {
        return [
            [
                'long' => 'max-jobs',
                'short' => 'm',
                'description' => 'The maximum number of jobs to process',
                'has_value' => true,
                'setter' => [$this, 'setMaxJobs'],
            ],
            [
                'long' => 'max-runtime',
                'short' => 'r',
                'description' => 'The maximum runtime of the worker',
                'has_value' => true,
                'setter' => [$this, 'setMaxRuntime'],
            ],
        ];
    }

    public function setMaxJobs(int $maxJobs): void
    {
        $this->maxJobs = $maxJobs;
    }

    public function setMaxRuntime(int $maxRuntime): void
    {
        $this->maxRuntime = $maxRuntime;
    }

    public function setQueueName(string $queueName): void
    {
        $this->queueName = $queueName;
    }

    public function execute(): void
    {
        if ($this->queueName === null)
        {
            throw new \RuntimeException('Queue name not set');
        }

        $queue = $this->queueService->get($this->queueName);

        $jobsProcessed = 0;
        $startTime = Carbon::now();

        while (true)
        {
            $job = $queue->popJob();

            if ($job === null)
            {
                Carbon::sleep(1);

                continue;
            }

            $jobHandler = $this->queueService->getJobHandler($job);
            $jobHandler->handle($job);

            $jobsProcessed++;

            if ($this->maxJobs !== null && $jobsProcessed >= $this->maxJobs)
            {
                break;
            }

            if ($this->maxRuntime !== null && $startTime->diffInSeconds() >= $this->maxRuntime)
            {
                break;
            }
        }
    }
}
