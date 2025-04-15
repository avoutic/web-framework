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

/**
 * Empty interface that can be extended to create a job.
 *
 * Jobs are supposed to only contain data, not logic.
 *
 * The logic should be implemented in a JobHandler class that is
 * registered in the Queue
 */
interface Job
{
    /**
     * Get the unique job ID.
     *
     * @return string The job ID
     */
    public function getJobId(): string;

    /**
     * The QueueService will set the job ID.
     *
     * @param string $jobId The job ID
     */
    public function setJobId(string $jobId): void;

    /**
     * Get the job name.
     *
     * @return string The job name
     */
    public function getJobName(): string;
}
