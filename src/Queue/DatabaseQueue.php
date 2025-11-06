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
    ) {}

    public function dispatch(Job $job, int $delay = 0): void
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
            ]);
        }
        else
        {
            $this->logger->debug('Dispatching job', [
                'queue' => $this->name,
                'jobId' => $job->getJobId(),
                'jobName' => $job->getJobName(),
            ]);
        }

        $this->queueJobRepository->create([
            'queue_name' => $this->name,
            'job_data' => serialize($job),
            'available_at' => $availableAt->getTimestamp(),
            'attempts' => 0,
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
        $job = unserialize($queueJob->getJobData());

        if (!($job instanceof Job))
        {
            // Invalid job data - release it back to queue
            $queueJob->setReservedAt(null);
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
     * Mark a job as successfully processed and delete it.
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

        $this->queueJobRepository->delete($queueJob);
        unset($this->jobIdToQueueJobId[$jobId]);

        $this->logger->debug('Job completed and deleted', [
            'queue' => $this->name,
            'jobId' => $jobId,
            'jobName' => $job->getJobName(),
        ]);
    }

    /**
     * Release a failed job back to the queue.
     */
    public function markJobFailed(Job $job): void
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

        $this->queueJobRepository->releaseJob($queueJob);
        unset($this->jobIdToQueueJobId[$jobId]);

        $this->logger->debug('Job failed and released back to queue', [
            'queue' => $this->name,
            'jobId' => $jobId,
            'jobName' => $job->getJobName(),
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
