<?php

namespace WebFramework\Core;

class NullReportFunction implements ReportFunction
{
    /**
     * @param array{title: string, message: string, low_info_message: string, hash:string} $debug_info
     */
    public function report(string $message, string $error_type, array $debug_info): void
    {
    }
}
