<?php

namespace WebFramework\Core;

/**
 * Interface ReportFunction.
 *
 * Defines the contract for error reporting implementations in the WebFramework.
 */
interface ReportFunction
{
    /**
     * Report an error or issue.
     *
     * @param string                                                                       $message   The error message
     * @param string                                                                       $errorType The type of error
     * @param array{title: string, message: string, low_info_message: string, hash:string} $debugInfo Additional debug information
     */
    public function report(string $message, string $errorType, array $debugInfo): void;
}
