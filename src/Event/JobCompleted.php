<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Event;

use WebFramework\Queue\Job;

/**
 * Dispatched by the queue worker after a job was handled and marked as completed.
 *
 * @codeCoverageIgnore
 */
class JobCompleted implements Event
{
    /**
     * @param Job    $job       The job that was completed
     * @param string $queueName The name of the queue the job came from
     * @param float  $duration  The time it took to handle the job, in seconds
     */
    public function __construct(
        public Job $job,
        public string $queueName,
        public float $duration,
    ) {}
}
