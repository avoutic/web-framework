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

use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use WebFramework\Exception\SanityCheckException;
use WebFramework\Task\SlimAppTask;
use WebFramework\Task\TaskRunner;

/**
 * Handles execution of the Slim application and error reporting for the HTTP entry point.
 */
class HttpApplication
{
    public function __construct(
        private DebugService $debugService,
        private LoggerInterface $logger,
        private LoggerInterface $exceptionLogger,
        private ReportFunction $reportFunction,
        private TaskRunner $taskRunner,
        private bool $debug,
    ) {}

    public function run(): void
    {
        try
        {
            $this->taskRunner->execute(SlimAppTask::class);
        }
        catch (SanityCheckException)
        {
            header('Content-type: text/plain');
            echo 'Sanity check failed';

            exit(1);
        }
        catch (\Throwable $e)
        {
            $this->handleException($e);
        }
    }

    private function handleException(\Throwable $throwable): void
    {
        $request = ServerRequestFactory::createFromGlobals();

        header('Content-type: text/html');

        try
        {
            $errorReport = $this->debugService->getThrowableReport($throwable, $request);

            $this->logger->error('Unhandled exception: '.$errorReport->getTitle());
            $this->exceptionLogger->error('Unhandled exception: '.$errorReport->getTitle(), [
                'error_report' => $errorReport,
                'hash' => $errorReport->getHash(),
            ]);

            $this->reportFunction->report($throwable->getMessage(), 'unhandled_exception', $errorReport);

            echo $this->debug ? $errorReport->getTitle() : 'An error occurred.';
        }
        catch (\Throwable $innerException)
        {
            $this->logger->error('Unhandled exception (without error report): '.$throwable->getMessage());
            $this->exceptionLogger->error('Unhandled exception (without error report): '.$throwable->getMessage(), [
                'exception' => $throwable,
                'inner_exception' => $innerException->getMessage(),
            ]);

            echo $this->debug
                ? 'Unhandled exception (without error report): '.$throwable->getMessage()
                : 'An error occurred.';
        }
    }
}
