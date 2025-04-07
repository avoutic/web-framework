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
 * Interface for job handlers.
 *
 * Job handlers are supposed to only contain logic, not data.
 *
 * The data is supposed to be contained in the Job class that is passed to the
 * handle method by a QueueWorker
 *
 * @template T of Job
 */
interface JobHandler
{
    /**
     * Handle the job.
     *
     * @param T $job The job to handle
     *
     * @return bool true if the job was properly handled and can be removed
     *              from the queue, false otherwise
     */
    public function handle(Job $job): bool;
}
