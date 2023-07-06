<?php

namespace WebFramework\Core;

class NullReportFunction implements ReportFunction
{
    /**
     * @param array{title: string, message: string, low_info_message: string, hash:string} $debugInfo
     */
    public function report(string $message, string $errorType, array $debugInfo): void
    {
    }
}
