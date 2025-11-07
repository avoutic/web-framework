<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Exception;

/**
 * Exception thrown when a job fails to execute (e.g., mail delivery failure).
 */
class JobExecutionException extends \Exception
{
    public function __construct(
        string $jobName,
        string $reason,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("Job {$jobName} failed: {$reason}", 0, $previous);
    }
}
