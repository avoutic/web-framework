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

interface Queue
{
    /**
     * Dispatch a Job to a queue.
     *
     * @param Job $job   Job to queue
     * @param int $delay Delay the job in seconds
     */
    public function dispatch(Job $job, int $delay = 0): void;

    /**
     * Count the number of jobs in a queue.
     */
    public function count(): int;

    /**
     * Retrieve the next Job on the queue.
     */
    public function popJob(): ?Job;

    /**
     * Get the name of the queue.
     */
    public function getName(): string;

    /**
     * Clear jobs from the queue.
     */
    public function clear(): void;

    /**
     * Mark a job as successfully processed.
     * For queues that remove jobs on pop, this may be a no-op.
     *
     * @param Job $job The job that was successfully processed
     */
    public function markJobCompleted(Job $job): void;

    /**
     * Mark a job as failed and release it back to the queue (if applicable).
     * For queues that remove jobs on pop, this may be a no-op.
     *
     * @param Job $job The job that failed
     */
    public function markJobFailed(Job $job): void;
}
