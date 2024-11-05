<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

/**
 * Class NullReportFunction.
 *
 * A null implementation of the ReportFunction interface that performs no actual reporting.
 * Useful for testing or when error reporting is disabled.
 */
class NullReportFunction implements ReportFunction
{
    /**
     * Report an error or issue.
     *
     * @param string                                                                       $message   The error message
     * @param string                                                                       $errorType The type of error
     * @param array{title: string, message: string, low_info_message: string, hash:string} $debugInfo Additional debug information
     */
    public function report(string $message, string $errorType, array $debugInfo): void
    {
        // No operation
    }
}
