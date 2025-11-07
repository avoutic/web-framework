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
 * Exception thrown when a job handler receives an invalid job type.
 */
class InvalidJobException extends \Exception
{
    public function __construct(
        string $expectedType,
        string $actualType,
    ) {
        parent::__construct("Invalid job type: expected {$expectedType}, got {$actualType}");
    }
}
