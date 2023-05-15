<?php

namespace WebFramework\Core;

use Slim\Psr7\Request;

class AssertService
{
    private int $in_verify = 0;             // Only go into an assert_handler for a maximum amount of times

    public function __construct(
        protected DebugService $debug_service,
        protected ReportFunction $report_function,
    ) {
    }

    /**
     * @param array<mixed> $stack
     */
    public function verify(bool|int $bool, string $message, array $stack = [], ?Request $request = null): void
    {
        if ($bool)
        {
            return;
        }

        // Prevent infinite verify loops
        //
        if ($this->in_verify > 2)
        {
            throw new \RuntimeException('2 deep into verifications');
        }

        $this->in_verify++;

        $this->report_error($message, $stack, $request, 'verify');

        throw new VerifyException($message);
    }

    /**
     * @param array<mixed> $stack
     */
    public function report_error(string $message, array $stack = [], ?Request $request = null, string $error_type = 'report_error'): void
    {
        $debug_info = $this->debug_service->get_error_report($stack, $request, 'report_error', $message);

        $this->report_function->report($message, 'Error reported', $debug_info);
    }
}
