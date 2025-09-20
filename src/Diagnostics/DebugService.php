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

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use WebFramework\Core\RuntimeEnvironment;
use WebFramework\Database\Database;
use WebFramework\Database\DatabaseProvider;
use WebFramework\Security\AuthenticationService;
use WebFramework\Support\ErrorReport;
use WebFramework\Support\Helpers;

/**
 * Class DebugService.
 *
 * Provides debugging and error reporting functionality for the application.
 */
class DebugService
{
    /**
     * DebugService constructor.
     *
     * @param AuthenticationService $authenticationService Service for handling authentication
     * @param DatabaseProvider      $databaseProvider      Provider for database access
     * @param ReportFunction        $reportFunction        Function for reporting errors
     * @param RuntimeEnvironment    $runtimeEnvironment    Runtime environment information
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private DatabaseProvider $databaseProvider,
        private ReportFunction $reportFunction,
        private RuntimeEnvironment $runtimeEnvironment,
    ) {}

    /**
     * Report an error.
     *
     * @param string       $message   The error message
     * @param array<mixed> $stack     The error stack trace
     * @param null|Request $request   The request object, if available
     * @param string       $errorType The type of error
     */
    public function reportError(string $message, array $stack = [], ?Request $request = null, string $errorType = 'report_error'): void
    {
        $errorReport = $this->getErrorReport($stack, $request, $errorType, $message);

        $this->reportFunction->report($message, $errorType, $errorReport);
    }

    /**
     * Report an exception.
     *
     * @param \Throwable   $e       The Throwable object
     * @param null|Request $request The request object, if available
     */
    public function reportException(\Throwable $e, ?Request $request = null): void
    {
        $errorReport = $this->getThrowableReport($e, $request);

        $this->reportFunction->report($e->getMessage(), $e::class, $errorReport);
    }

    /**
     * Get a report for a Throwable.
     *
     * @param \Throwable   $e       The Throwable object
     * @param null|Request $request The request object, if available
     *
     * @return ErrorReport The error report
     */
    public function getThrowableReport(\Throwable $e, ?Request $request = null): ErrorReport
    {
        $stack = $this->filterTrace($e->getTrace());

        $file = $e->getFile();
        $line = $e->getLine();
        $errorType = $e::class;
        $message = $e->getMessage();

        return $this->getReport($file, $line, $stack, $request, $errorType, $message);
    }

    /**
     * Get an error report from a stack trace.
     *
     * @param array<mixed> $trace     The stack trace
     * @param null|Request $request   The request object, if available
     * @param string       $errorType The type of error
     * @param string       $message   The error message
     *
     * @return ErrorReport The error report
     */
    public function getErrorReport(array $trace, ?Request $request, string $errorType, string $message): ErrorReport
    {
        $stack = $this->filterTrace($trace);
        $stackTop = reset($stack);

        $file = ($stackTop) ? $stackTop['file'] : 'unknown';
        $line = ($stackTop) ? $stackTop['line'] : 0;

        return $this->getReport($file, $line, $stack, $request, $errorType, $message);
    }

    /**
     * Generate a detailed error report.
     *
     * @param string       $file          The file where the error occurred
     * @param int          $line          The line number where the error occurred
     * @param array<mixed> $filteredStack The filtered stack trace
     * @param null|Request $request       The request object, if available
     * @param string       $errorType     The type of error
     * @param string       $message       The error message
     *
     * @return ErrorReport The detailed error report
     */
    private function getReport(string $file, int $line, array $filteredStack, ?Request $request, string $errorType, string $message): ErrorReport
    {
        $report = new ErrorReport();

        $report->message = $message;

        // Retrieve request
        //
        if ($request !== null)
        {
            try
            {
                $routeContext = RouteContext::fromRequest($request);
                $route = $routeContext->getRoute();

                // Use route name as base source
                //
                if (!empty($route))
                {
                    $requestMethod = $request->getMethod();

                    $report->requestSource = $requestMethod.' '.$route->getPattern();
                }
            }
            catch (\RuntimeException $e)
            {
            }

            // Fallback to direct method
            //
            if ($report->requestSource === 'unknown')
            {
                $requestMethod = $request->getMethod();

                $uri = $request->getUri();

                $report->requestSource = $requestMethod.' '.$uri->getPath();
            }
        }

        $report->serverName = $this->runtimeEnvironment->getServerName();
        $report->file = $file;
        $report->line = $line;

        $report->errorType = Helpers::getErrorTypeString($errorType);
        $report->stack = $this->condenseStack($filteredStack);
        $report->dbError = $this->getDatabaseError($this->databaseProvider->get());
        $report->auth = $this->getAuthenticationStatus();

        if ($request !== null)
        {
            $report->inputs = $this->getInputsReport($request);

            $headers = $request->getHeaders();
            $report->headers = $this->scrubRequestHeaders($headers);

            $report->serverParams = $request->getServerParams();
        }

        return $report;
    }

    /**
     * Filter a stack trace.
     *
     * @param array<array<mixed>> $trace        The original stack trace
     * @param bool                $skipInternal Whether to skip internal frames
     * @param bool                $scrubState   Whether to scrub sensitive state information
     *
     * @return array<array<mixed>> The filtered stack trace
     */
    public function filterTrace(array $trace, bool $skipInternal = true, bool $scrubState = true): array
    {
        $stack = [];
        $skipping = $skipInternal;

        foreach ($trace as $entry)
        {
            if ($skipping && isset($entry['class'])
                && $entry['class'] === 'DebugService')
            {
                continue;
            }

            $skipping = false;

            if ($scrubState)
            {
                if (isset($entry['args']))
                {
                    Helpers::scrubState($entry['args']);
                }
            }

            $stack[] = $entry;
        }

        return $stack;
    }

    /**
     * Condense a stack trace into a shorter representation.
     *
     * @param array<array<mixed>> $stack The stack trace to condense
     *
     * @return array<string> The condensed stack trace
     */
    public function condenseStack(array $stack): array
    {
        $appDir = $this->runtimeEnvironment->getAppDir();

        $stackCondensed = [];

        foreach ($stack as $entry)
        {
            $file = $entry['file'] ?? 'unknown';

            if (str_starts_with($file, $appDir))
            {
                $file = substr($file, strlen($appDir));
            }

            $line = $entry['line'] ?? '-';

            $function = '';
            if (isset($entry['class']))
            {
                $pattern = '/@anonymous.*/';
                $replacement = '@anonymous';
                $class = preg_replace($pattern, $replacement, $entry['class']);
                $function .= $class.$entry['type'];
            }

            $function = $function.$entry['function'].'()';

            $stackCondensed[] = "{$function} ({$file}:{$line})";
        }

        return $stackCondensed;
    }

    /**
     * Get the last database error.
     *
     * @param null|Database $database The database object
     *
     * @return string The last database error or 'None' if no error
     */
    public function getDatabaseError(?Database $database): string
    {
        if ($database === null)
        {
            return 'Not initialized yet';
        }

        $dbError = $database->getLastError();

        if (strlen($dbError))
        {
            return $dbError;
        }

        return 'None';
    }

    /**
     * Get the current authentication status.
     *
     * @return null|array{user_id: int, username: string, email: string} The authentication status
     */
    public function getAuthenticationStatus(): ?array
    {
        if (!$this->authenticationService->isAuthenticated())
        {
            return null;
        }

        $user = $this->authenticationService->getAuthenticatedUser();

        return [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ];
    }

    /**
     * Get a report of the request inputs.
     *
     * @param Request $request The request object
     *
     * @return array<string, mixed>
     */
    public function getInputsReport(Request $request): array
    {
        $inputs = [];

        // Get the GET parameters
        //
        $getParams = $request->getQueryParams();

        if (count($getParams))
        {
            $inputs['get'] = $getParams;
        }

        // Check if the Content-Type header indicates JSON data
        //
        $contentType = $request->getHeaderLine('Content-Type');
        $isJsonData = str_contains($contentType, 'application/json');

        if ($isJsonData)
        {
            // Get the message body as a string
            //
            $body = (string) $request->getBody();

            // Parse the JSON content
            //
            $jsonData = json_decode($body, true);

            if ($jsonData === null && json_last_error() !== JSON_ERROR_NONE)
            {
                $inputs['json_parsing_failed'] = $body;
            }
            else
            {
                $inputs['json_data'] = $jsonData;
            }
        }

        // Check if parsed body data is available
        //
        $postParams = $request->getParsedBody();
        if ($postParams !== null)
        {
            $inputs['post'] = $postParams;
        }

        return $inputs;
    }

    /**
     * Scrub sensitive information from request headers.
     *
     * @param array<array<mixed>> $headers The original headers
     *
     * @return array<array<mixed>> The scrubbed headers
     */
    public function scrubRequestHeaders(array $headers): array
    {
        foreach ($headers as $name => $values)
        {
            // Exclude cookie headers
            //
            if (strtolower($name) === 'cookie')
            {
                unset($headers[$name]);
            }
        }

        return $headers;
    }
}
