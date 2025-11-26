<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Queue;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use WebFramework\Repository\QueueJobRepository;

class DatabaseQueue implements Queue
{
    /** @var array<string, int> Mapping of Job ID to QueueJob ID */
    private array $jobIdToQueueJobId = [];

    public function __construct(
        private LoggerInterface $logger,
        private QueueJobRepository $queueJobRepository,
        private string $name,
        private bool $deleteJobsOnCompletion = true,
    ) {}

    public function dispatch(Job $job, int $delay = 0, int $maxAttempts = 3): void
    {
        $availableAt = Carbon::now();
        if ($delay > 0)
        {
            $availableAt->addSeconds($delay);
            $this->logger->debug('Dispatching delayed job', [
                'queue' => $this->name,
                'jobId' => $job->getJobId(),
                'jobName' => $job->getJobName(),
                'delay' => $delay,
                'maxAttempts' => $maxAttempts,
            ]);
        }
        else
        {
            $this->logger->debug('Dispatching job', [
                'queue' => $this->name,
                'jobId' => $job->getJobId(),
                'jobName' => $job->getJobName(),
                'maxAttempts' => $maxAttempts,
            ]);
        }

        $this->queueJobRepository->create([
            'queue_name' => $this->name,
            'job_data' => serialize($job),
            'available_at' => $availableAt->getTimestamp(),
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
        ]);
    }

    public function count(): int
    {
        return $this->queueJobRepository->countJobsInQueue($this->name);
    }

    public function popJob(): ?Job
    {
        $queueJob = $this->queueJobRepository->getNextJob($this->name);

        if ($queueJob === null)
        {
            return null;
        }

        // Don't delete yet - job is locked and will be deleted after successful processing
        try
        {
            $job = unserialize($queueJob->getJobData());
        }
        catch (\Throwable $e)
        {
            // Corrupted job data - release it back to queue and mark as failed
            $queueJob->setReservedAt(null);
            $queueJob->setAttempts($queueJob->getAttempts() + 1);
            $errorMessage = sprintf(
                'Unserialize failed: %s: %s',
                get_class($e),
                $e->getMessage()
            );
            $queueJob->setError($errorMessage);
            $this->queueJobRepository->save($queueJob);

            $this->logger->error('Failed to unserialize job data', [
                'queue' => $this->name,
                'queueJobId' => $queueJob->getId(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (!($job instanceof Job))
        {
            // Invalid job data - release it back to queue
            $queueJob->setReservedAt(null);
            $queueJob->setAttempts($queueJob->getAttempts() + 1);
            $errorMessage = 'Invalid job data: unserialized value is not a Job instance';
            $queueJob->setError($errorMessage);
            $this->queueJobRepository->save($queueJob);

            $this->logger->warning('Invalid job data encountered', [
                'queue' => $this->name,
                'jobId' => $queueJob->getId(),
            ]);

            return null;
        }

        // Store mapping of Job ID to QueueJob ID for later cleanup
        $this->jobIdToQueueJobId[$job->getJobId()] = $queueJob->getId();

        return $job;
    }

    /**
     * Mark a job as successfully processed and delete it if $deleteJobsOnCompletion is true.
     */
    public function markJobCompleted(Job $job): void
    {
        $jobId = $job->getJobId();

        if (!isset($this->jobIdToQueueJobId[$jobId]))
        {
            $this->logger->warning('Attempted to complete unknown job', [
                'queue' => $this->name,
                'jobId' => $jobId,
            ]);

            return;
        }

        $queueJobId = $this->jobIdToQueueJobId[$jobId];
        $queueJob = $this->queueJobRepository->getObjectById($queueJobId);

        if ($queueJob === null)
        {
            unset($this->jobIdToQueueJobId[$jobId]);
            $this->logger->warning('QueueJob not found for completed job', [
                'queue' => $this->name,
                'jobId' => $jobId,
                'queueJobId' => $queueJobId,
            ]);

            return;
        }

        if ($this->deleteJobsOnCompletion)
        {
            $this->queueJobRepository->delete($queueJob);
        }
        else
        {
            $queueJob->setCompletedAt(Carbon::now()->getTimestamp());
            $this->queueJobRepository->save($queueJob);
        }

        unset($this->jobIdToQueueJobId[$jobId]);

        $this->logger->debug('Job completed and deleted', [
            'queue' => $this->name,
            'jobId' => $jobId,
            'jobName' => $job->getJobName(),
        ]);
    }

    /**
     * Release a failed job back to the queue with retry logic.
     */
    public function markJobFailed(Job $job, ?\Throwable $exception = null): void
    {
        $jobId = $job->getJobId();

        if (!isset($this->jobIdToQueueJobId[$jobId]))
        {
            $this->logger->warning('Attempted to fail unknown job', [
                'queue' => $this->name,
                'jobId' => $jobId,
            ]);

            return;
        }

        $queueJobId = $this->jobIdToQueueJobId[$jobId];
        $queueJob = $this->queueJobRepository->getObjectById($queueJobId);

        if ($queueJob === null)
        {
            unset($this->jobIdToQueueJobId[$jobId]);
            $this->logger->warning('QueueJob not found for failed job', [
                'queue' => $this->name,
                'jobId' => $jobId,
                'queueJobId' => $queueJobId,
            ]);

            return;
        }

        $currentAttempts = $queueJob->getAttempts();
        $maxAttempts = $queueJob->getMaxAttempts();

        // Store error details
        $errorMessage = null;
        if ($exception !== null)
        {
            $errorMessage = sprintf(
                '%s: %s',
                get_class($exception),
                $exception->getMessage()
            );
            $queueJob->setError($errorMessage);
        }

        // Check if we've exceeded max attempts
        if ($currentAttempts >= $maxAttempts - 1)
        {
            unset($this->jobIdToQueueJobId[$jobId]);

            $queueJob->setAttempts($queueJob->getAttempts() + 1);
            $queueJob->setFailedAt(Carbon::now()->getTimestamp());

            // Move to dead letter queue (same table, different queue name)
            $deadLetterQueueName = $this->name.'-failed';
            $queueJob->setQueueName($deadLetterQueueName);
            $this->queueJobRepository->save($queueJob);

            $this->logger->error('Job failed after max attempts, moved to dead letter queue', [
                'queue' => $this->name,
                'deadLetterQueue' => $deadLetterQueueName,
                'jobId' => $jobId,
                'jobName' => $job->getJobName(),
                'attempts' => $currentAttempts + 1,
                'maxAttempts' => $maxAttempts,
                'error' => $errorMessage,
            ]);

            return;
        }

        // Calculate exponential backoff: 2^attempts seconds (min 1 second)
        $backoffSeconds = (int) max(1, 2 ** $currentAttempts);

        // Reschedule with backoff - update entity and save
        // Clear error field on retry so old errors don't persist if retry succeeds
        $newAvailableAt = Carbon::now()->addSeconds($backoffSeconds)->getTimestamp();
        $queueJob->setReservedAt(null);
        $queueJob->setAvailableAt($newAvailableAt);
        $queueJob->setAttempts($currentAttempts + 1);
        $queueJob->setError(null);
        $this->queueJobRepository->save($queueJob);

        unset($this->jobIdToQueueJobId[$jobId]);

        $this->logger->warning('Job failed, rescheduling with backoff', [
            'queue' => $this->name,
            'jobId' => $jobId,
            'jobName' => $job->getJobName(),
            'attempts' => $currentAttempts + 1,
            'maxAttempts' => $maxAttempts,
            'backoffSeconds' => $backoffSeconds,
            'error' => $errorMessage,
        ]);
    }

    public function clear(): void
    {
        $this->logger->debug('Clearing queue', ['queue' => $this->name]);

        $this->queueJobRepository->clearQueue($this->name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
