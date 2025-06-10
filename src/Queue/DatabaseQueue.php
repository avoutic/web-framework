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

        $this->queueJobRepository->delete($queueJob);

        $job = unserialize($queueJob->getJobData());

        return $job instanceof Job ? $job : null;
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
