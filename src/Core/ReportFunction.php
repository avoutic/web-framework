<?php

namespace WebFramework\Core;

interface ReportFunction
{
    /**
     * @param array{title: string, message: string, low_info_message: string, hash:string} $debugInfo
     */
    public function report(string $message, string $errorType, array $debugInfo): void;
}
