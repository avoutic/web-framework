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
use WebFramework\Exception\ArgumentParserException;
use WebFramework\Logging\LogService;
use WebFramework\Queue\QueueService;

class QueueWorker extends ConsoleTask
{
    private ?int $maxJobs = null;
    private ?int $maxRuntime = null;

    public function __construct(
        private QueueService $queueService,
        private LogService $logger,
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
            new TaskArgument('queueName', 'The name of the queue to work', true, [$this, 'setQueueName']),
        ];
    }

    public function getOptions(): array
    {
        return [
            new TaskOption('max-jobs', 'm', 'The maximum number of jobs to process', true, [$this, 'setMaxJobs']),
            new TaskOption('max-runtime', 'r', 'The maximum runtime of the worker', true, [$this, 'setMaxRuntime']),
        ];
    }

    public function setMaxJobs(string $maxJobs): void
    {
        if (!is_numeric($maxJobs))
        {
            throw new ArgumentParserException('Max jobs must be a number');
        }

        $this->maxJobs = (int) $maxJobs;
    }

    public function setMaxRuntime(string $maxRuntime): void
    {
        if (!is_numeric($maxRuntime))
        {
            throw new ArgumentParserException('Max runtime must be a number');
        }

        $this->maxRuntime = (int) $maxRuntime;
    }

    public function setQueueName(string $queueName): void
    {
        $this->queueName = $queueName;
    }

    public function execute(): void
    {
        if ($this->queueName === null)
        {
            throw new ArgumentParserException('Queue name not set');
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

            try
            {
                $jobHandler = $this->queueService->getJobHandler($job);
                $success = $jobHandler->handle($job);

                // Mark job as completed or failed based on handler return value
                if ($success)
                {
                    $queue->markJobCompleted($job);
                }
                else
                {
                    $queue->markJobFailed($job);
                }

                $jobsProcessed++;
            }
            catch (\Throwable $e)
            {
                // Log to default channel with simple message
                $this->logger->error('default', 'Job execution failed: '.get_class($e).': '.$e->getMessage());

                // Log to exception channel with detailed context
                $this->logger->error('exception', 'Job execution failed with exception', [
                    'queue' => $this->queueName,
                    'jobId' => $job->getJobId(),
                    'jobName' => $job->getJobName(),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Handle exceptions - mark job as failed with exception details
                $queue->markJobFailed($job, $e);

                // Continue processing instead of crashing the worker
                $jobsProcessed++;
            }

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
