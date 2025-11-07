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
 * Exception thrown when a job is missing required data.
 */
class JobDataException extends \Exception
{
    public function __construct(
        string $jobName,
        string $missingField,
    ) {
        parent::__construct("Job {$jobName} is missing required field: {$missingField}");
    }
}
