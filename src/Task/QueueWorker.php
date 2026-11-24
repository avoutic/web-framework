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
use WebFramework\Database\Database;
use WebFramework\Exception\ArgumentParserException;
use WebFramework\Exception\JobExecutionException;
use WebFramework\Logging\LogService;
use WebFramework\Queue\Job;
use WebFramework\Queue\QueueService;

class QueueWorker extends ConsoleTask
{
    private int $emptySleep = 1;
    private ?int $maxJobs = null;
    private ?int $maxRuntime = null;

    public function __construct(
        private Database $database,
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
        --empty-sleep=<emptySleep> The number of seconds to sleep when there are no jobs (default: 1)
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
            new TaskOption('empty-sleep', 'e', 'The number of seconds to sleep when there are no jobs', true, [$this, 'setEmptySleep']),
            new TaskOption('max-jobs', 'm', 'The maximum number of jobs to process', true, [$this, 'setMaxJobs']),
            new TaskOption('max-runtime', 'r', 'The maximum runtime of the worker', true, [$this, 'setMaxRuntime']),
        ];
    }

    public function getEmptySleep(): ?int
    {
        return $this->emptySleep;
    }

    public function setEmptySleep(string $emptySleep): void
    {
        $this->emptySleep = (int) $emptySleep;
    }

    public function getMaxJobs(): ?int
    {
        return $this->maxJobs;
    }

    public function setMaxJobs(string $maxJobs): void
    {
        if (!is_numeric($maxJobs) || $maxJobs < 1)
        {
            throw new ArgumentParserException('Max jobs must be a number greater than 0');
        }

        $this->maxJobs = (int) $maxJobs;
    }

    public function getMaxRuntime(): ?int
    {
        return $this->maxRuntime;
    }

    public function setMaxRuntime(string $maxRuntime): void
    {
        if (!is_numeric($maxRuntime) || $maxRuntime < 1)
        {
            throw new ArgumentParserException('Max runtime must be a number greater than 0');
        }

        $this->maxRuntime = (int) $maxRuntime;
    }

    public function getQueueName(): ?string
    {
        return $this->queueName;
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
            // Checked on every pass, also when the queue is empty, so an idle worker
            // stops at its maximum runtime as well
            //
            if ($this->maxJobs !== null && $jobsProcessed >= $this->maxJobs)
            {
                break;
            }

            if ($this->maxRuntime !== null && $startTime->diffInSeconds() >= $this->maxRuntime)
            {
                break;
            }

            $this->logger->debug('default', 'Popping job from queue', ['queue' => $this->queueName]);
            $job = $queue->popJob();

            if ($job === null)
            {
                $this->logger->debug('default', 'No job found, sleeping', [
                    'queue' => $this->queueName,
                    'delay' => $this->emptySleep,
                ]);

                Carbon::sleep($this->emptySleep);

                continue;
            }

            try
            {
                $jobHandler = $this->queueService->getJobHandler($job);
                $jobHandler->handle($job);

                // A handler that returns with a transaction still open has not stored
                // its work. Treat that as a failure, not as a completed job
                //
                if ($this->rollbackOpenTransactions($job))
                {
                    throw new JobExecutionException($job->getJobName(), 'the handler left a database transaction open');
                }

                // Mark job as completed if no exception was thrown
                $queue->markJobCompleted($job);

                $jobsProcessed++;
            }
            catch (\Throwable $e)
            {
                // A transaction the handler left open would swallow everything after it:
                // the failure of this job, and every job that follows
                //
                $this->rollbackOpenTransactions($job);

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
        }
    }

    /**
     * Roll back every database transaction that is still open after a job.
     *
     * @param Job $job The job that was handled
     *
     * @return bool True if a transaction was open
     */
    private function rollbackOpenTransactions(Job $job): bool
    {
        $depth = $this->database->getTransactionDepth();

        if ($depth <= 0)
        {
            return false;
        }

        $this->logger->warning('default', 'Job left a database transaction open, rolling back', [
            'queue' => $this->queueName,
            'jobId' => $job->getJobId(),
            'jobName' => $job->getJobName(),
            'depth' => $depth,
        ]);

        // One rollback per level. Stop when a rollback does not lower the depth, to not
        // loop forever on a database that does not track it
        //
        while ($depth > 0)
        {
            $this->database->rollbackTransaction();

            $newDepth = $this->database->getTransactionDepth();

            if ($newDepth >= $depth)
            {
                break;
            }

            $depth = $newDepth;
        }

        return true;
    }
}
