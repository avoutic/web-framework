<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Diagnostics;

use WebFramework\Support\ErrorReport;

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
     * @param string      $message     The error message
     * @param string      $errorType   The type of error
     * @param ErrorReport $errorReport The error report
     */
    public function report(string $message, string $errorType, ErrorReport $errorReport): void
    {
        // No operation
    }
}
