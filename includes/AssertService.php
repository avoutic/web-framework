<?php

namespace WebFramework\Core;

use Psr\Http\Message\ServerRequestInterface as Request;

class AssertService
{
    private int $in_verify = 0;             // Only go into an assert_handler for a maximum amount of times

    public function __construct(
        protected DebugService $debug_service,
        protected ReportFunction $report_function,
    ) {
    }

    public function verify(bool|int $bool, string $message, string $exception_class = VerifyException::class): void
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

        if (!is_subclass_of($exception_class, \Throwable::class))
        {
            throw new \RuntimeException($message);
        }

        throw new $exception_class($message);
    }

    /**
     * @param array<mixed> $stack
     */
    public function report_error(string $message, array $stack = [], ?Request $request = null, string $error_type = 'report_error'): void
    {
        $debug_info = $this->debug_service->get_error_report($stack, $request, $error_type, $message);

        $this->report_function->report($message, $error_type, $debug_info);
    }
}
