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
 * Dispatched by the queue worker after an attempt to handle a job failed. The job may be
 * tried again, or have been moved to the dead letter queue.
 *
 * The exception is given as its class and message, so the event can be handled by a
 * queued listener: an exception itself cannot always be stored.
 *
 * @codeCoverageIgnore
 */
class JobFailed implements Event
{
    /**
     * @param Job    $job              The job that failed
     * @param string $queueName        The name of the queue the job came from
     * @param float  $duration         The time the attempt took, in seconds
     * @param string $exceptionClass   The class of the exception
     * @param string $exceptionMessage The message of the exception
     */
    public function __construct(
        public Job $job,
        public string $queueName,
        public float $duration,
        public string $exceptionClass,
        public string $exceptionMessage,
    ) {}
}
