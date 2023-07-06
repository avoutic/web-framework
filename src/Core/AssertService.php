<?php

namespace WebFramework\Core;

use Psr\Http\Message\ServerRequestInterface as Request;
use WebFramework\Exception\VerifyException;

class AssertService
{
    private int $inVerify = 0;             // Only go into an assert_handler for a maximum amount of times

    public function __construct(
        protected DebugService $debugService,
        protected ReportFunction $reportFunction,
    ) {
    }

    public function verify(bool|int $bool, string $message, string $exceptionClass = VerifyException::class): void
    {
        if ($bool)
        {
            return;
        }

        // Prevent infinite verify loops
        //
        if ($this->inVerify > 2)
        {
            throw new \RuntimeException('2 deep into verifications');
        }

        $this->inVerify++;

        if (!is_subclass_of($exceptionClass, \Throwable::class))
        {
            throw new \RuntimeException($message);
        }

        throw new $exceptionClass($message);
    }

    /**
     * @param array<mixed> $stack
     */
    public function reportError(string $message, array $stack = [], ?Request $request = null, string $errorType = 'report_error'): void
    {
        $debugInfo = $this->debugService->getErrorReport($stack, $request, $errorType, $message);

        $this->reportFunction->report($message, $errorType, $debugInfo);
    }
}
