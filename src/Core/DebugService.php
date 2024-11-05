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

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use WebFramework\Security\AuthenticationService;

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
     * Generate a hash for caching error reports.
     *
     * @param string $serverName    The server name
     * @param string $requestSource The source of the request
     * @param string $file          The file where the error occurred
     * @param int    $line          The line number where the error occurred
     * @param string $message       The error message
     *
     * @return string The generated hash
     */
    public function generateHash(string $serverName, string $requestSource, string $file, int $line, string $message): string
    {
        $key = "{$serverName}:{$requestSource}:{$file}:{$line}:{$message}";

        return sha1($key);
    }

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
        $debugInfo = $this->getErrorReport($stack, $request, $errorType, $message);

        $this->reportFunction->report($message, $errorType, $debugInfo);
    }

    /**
     * Get a report for a Throwable.
     *
     * @param \Throwable   $e       The Throwable object
     * @param null|Request $request The request object, if available
     *
     * @return array{title: string, low_info_message: string, message: string, hash: string} The error report
     */
    public function getThrowableReport(\Throwable $e, ?Request $request = null): array
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
     * @return array{title: string, low_info_message: string, message: string, hash: string} The error report
     */
    public function getErrorReport(array $trace, ?Request $request, string $errorType, string $message): array
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
     * @return array{title: string, low_info_message: string, message: string, hash: string} The detailed error report
     */
    private function getReport(string $file, int $line, array $filteredStack, ?Request $request, string $errorType, string $message): array
    {
        $info = [
            'title' => "{$this->runtimeEnvironment->getServerName()} - {$errorType}: {$message}",
            'low_info_message' => '',
            'message' => '',
            'hash' => '',
        ];

        // Retrieve request
        //
        $requestSource = 'unknown';
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

                    $requestSource = $requestMethod.' '.$route->getPattern();
                }
            }
            catch (\RuntimeException $e)
            {
            }

            // Fallback to direct method
            //
            if ($requestSource === 'unknown')
            {
                $requestMethod = $request->getMethod();

                $uri = $request->getUri();

                $requestSource = $requestMethod.' '.$uri->getPath();
            }
        }

        // Cache hash
        //
        $info['hash'] = $this->generateHash(
            $this->runtimeEnvironment->getServerName(),
            $requestSource,
            $file,
            $line,
            $message,
        );

        // Construct base message
        //
        $info['low_info_message'] = <<<'TXT'
An error occurred.

TXT;

        $errorType = WFHelpers::getErrorTypeString($errorType);
        $condensedStack = $this->condenseStack($filteredStack);

        $dbError = $this->getDatabaseError($this->databaseProvider->get());

        $inputReport = "No request\n";
        $headersFmt = "No request\n";
        $serverFmt = "No request\n";

        if ($request !== null)
        {
            $inputReport = $this->getInputsReport($request);
            $headers = $request->getHeaders();
            $headers = $this->scrubRequestHeaders($headers);
            $headersFmt = print_r($headers, true);
            $serverFmt = print_r($request->getServerParams(), true);
        }

        $authData = $this->getAuthenticationStatus();

        $info['message'] .= <<<TXT
File: {$file}
Line: {$line}
ErrorType: {$errorType}
Message: {$message}

Server: {$this->runtimeEnvironment->getServerName()}
Request: {$requestSource}

Condensed backtrace:
{$condensedStack}
Last Database error:
{$dbError}

Inputs:
{$inputReport}
Auth:
{$authData}
Headers:
{$headersFmt}
Server:
{$serverFmt}
TXT;

        return $info;
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

            if (in_array($entry['function'], ['exit_send_error', 'exit_error']))
            {
                unset($entry['args']);
            }

            if ($scrubState)
            {
                if (isset($entry['args']))
                {
                    WFHelpers::scrubState($entry['args']);
                }
            }

            $stack[] = $entry;
        }

        return $stack;
    }

    /**
     * Condense a stack trace into a string representation.
     *
     * @param array<array<mixed>> $stack The stack trace to condense
     *
     * @return string The condensed stack trace
     */
    public function condenseStack(array $stack): string
    {
        $stackCondensed = '';

        foreach ($stack as $entry)
        {
            $file = $entry['file'] ?? 'unknown';
            $line = $entry['line'] ?? '-';
            $stackCondensed .= $file.'('.$line.'): ';

            if (isset($entry['class']))
            {
                $pattern = '/@anonymous.*/';
                $replacement = '@anonymous';
                $class = preg_replace($pattern, $replacement, $entry['class']);
                $stackCondensed .= $class.$entry['type'];
            }

            $stackCondensed .= $entry['function']."()\n";
            if (isset($entry['args']) && count($entry['args']))
            {
                $stackCondensed .= print_r($entry['args'], true)."\n";
            }
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
     * @return string A string representation of the authentication status
     */
    public function getAuthenticationStatus(): string
    {
        $authData = "Not authenticated\n";

        if ($this->authenticationService->isAuthenticated())
        {
            $user = $this->authenticationService->getAuthenticatedUser();
            $authArray = [
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ];

            $authData = print_r($authArray, true);
        }

        return $authData;
    }

    /**
     * Get a report of the request inputs.
     *
     * @param Request $request The request object
     *
     * @return string A string representation of the request inputs
     */
    public function getInputsReport(Request $request): string
    {
        $inputsFmt = '';

        // Get the GET parameters
        //
        $getParams = $request->getQueryParams();

        if (count($getParams))
        {
            $getFmt = print_r($getParams, true);

            $inputsFmt .= <<<TXT
GET:
{$getFmt}

TXT;
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
                // Error parsing
                //
                $inputsFmt .= <<<TXT
JSON parsing failed:
{$body}

TXT;
            }
            else
            {
                $jsonFmt = print_r($jsonData, true);

                $inputsFmt .= <<<TXT
JSON data:
{$jsonFmt}

TXT;
            }
        }

        // Check if parsed body data is available
        //
        $postParams = $request->getParsedBody();
        if ($postParams !== null)
        {
            $postFmt = print_r($postParams, true);

            $inputsFmt .= <<<TXT
POST:
{$postFmt}

TXT;
        }

        return strlen($inputsFmt) ? $inputsFmt : "No inputs\n";
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
