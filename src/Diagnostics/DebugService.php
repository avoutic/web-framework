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
use Psr\Log\LoggerInterface;
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
    /** The value that replaces scrubbed content in a report */
    public const SCRUBBED = 'scrubbed';

    /** Request headers whose name contains one of these (case-insensitive) are scrubbed */
    public const DEFAULT_SCRUB_HEADERS = ['authorization', 'cookie', 'token', 'secret', 'key'];

    /** Inputs whose key contains one of these (case-insensitive) are scrubbed */
    public const DEFAULT_SCRUB_INPUTS = ['password', 'passwd', 'secret', 'token', 'authorization', 'code'];

    /** Server params that are reported, all others are left out */
    public const DEFAULT_SERVER_PARAMS = [
        'REQUEST_METHOD',
        'REQUEST_URI',
        'QUERY_STRING',
        'REQUEST_SCHEME',
        'SERVER_PROTOCOL',
        'HTTPS',
        'SERVER_NAME',
        'SERVER_PORT',
        'HTTP_HOST',
        'REMOTE_ADDR',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_USER_AGENT',
        'HTTP_REFERER',
        'HTTP_ACCEPT',
        'HTTP_ACCEPT_LANGUAGE',
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        'REQUEST_TIME',
    ];

    /** @var array<string> */
    private array $scrubHeaders = self::DEFAULT_SCRUB_HEADERS;

    /** @var array<string> */
    private array $scrubInputs = self::DEFAULT_SCRUB_INPUTS;

    /** @var array<string> */
    private array $serverParams = self::DEFAULT_SERVER_PARAMS;

    /**
     * DebugService constructor.
     *
     * @param AuthenticationService $authenticationService Service for handling authentication
     * @param DatabaseProvider      $databaseProvider      Provider for database access
     * @param LoggerInterface       $logger                The logger
     * @param ReportFunction        $reportFunction        Function for reporting errors
     * @param RuntimeEnvironment    $runtimeEnvironment    Runtime environment information
     * @param null|array<string>    $scrubHeaders          Parts of header names to scrub (null for the defaults)
     * @param null|array<string>    $scrubInputs           Parts of input keys to scrub (null for the defaults)
     * @param null|array<string>    $serverParams          Server params to report (null for the defaults)
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private DatabaseProvider $databaseProvider,
        private LoggerInterface $logger,
        private ReportFunction $reportFunction,
        private RuntimeEnvironment $runtimeEnvironment,
        ?array $scrubHeaders = null,
        ?array $scrubInputs = null,
        ?array $serverParams = null,
    ) {
        $this->scrubHeaders = $scrubHeaders ?? self::DEFAULT_SCRUB_HEADERS;
        $this->scrubInputs = $scrubInputs ?? self::DEFAULT_SCRUB_INPUTS;
        $this->serverParams = $serverParams ?? self::DEFAULT_SERVER_PARAMS;
    }

    /**
     * Report an error.
     *
     * This never throws. A failure to report is logged.
     *
     * @param string       $message   The error message
     * @param array<mixed> $stack     The error stack trace
     * @param null|Request $request   The request object, if available
     * @param string       $errorType The type of error
     */
    public function reportError(string $message, array $stack = [], ?Request $request = null, string $errorType = 'report_error'): void
    {
        try
        {
            $errorReport = $this->getErrorReport($stack, $request, $errorType, $message);

            $this->reportFunction->report($message, $errorType, $errorReport);
        }
        catch (\Throwable $reportError)
        {
            $this->logReportFailure($reportError, $message);
        }
    }

    /**
     * Report an exception.
     *
     * This never throws. A failure to report is logged.
     *
     * @param \Throwable   $e       The Throwable object
     * @param null|Request $request The request object, if available
     */
    public function reportException(\Throwable $e, ?Request $request = null): void
    {
        try
        {
            $errorReport = $this->getThrowableReport($e, $request);

            $this->reportFunction->report($e->getMessage(), $e::class, $errorReport);
        }
        catch (\Throwable $reportError)
        {
            $this->logReportFailure($reportError, $e->getMessage());
        }
    }

    /**
     * Log that an error could not be reported. Reporting an error must never cause a new one.
     *
     * @param \Throwable $reportError The exception thrown while reporting
     * @param string     $message     The message of the error that was being reported
     */
    private function logReportFailure(\Throwable $reportError, string $message): void
    {
        try
        {
            $this->logger->error('Failed to report error', [
                'reason' => $reportError->getMessage(),
                'error' => $message,
            ]);
        }
        catch (\Throwable $loggingError)
        {
            // Nothing left to tell it to
        }
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

            $report->serverParams = $this->scrubServerParams($request->getServerParams());
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
            $inputs['get'] = $this->scrubInputs($getParams);
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
                // The raw body cannot be scrubbed by key, so only report its size
                //
                $inputs['json_parsing_failed'] = 'Unparseable body of '.strlen($body).' bytes';
            }
            else
            {
                $inputs['json_data'] = is_array($jsonData) ? $this->scrubInputs($jsonData) : $jsonData;
            }
        }

        // Check if parsed body data is available
        //
        $postParams = $request->getParsedBody();
        if ($postParams !== null)
        {
            $inputs['post'] = is_array($postParams) ? $this->scrubInputs($postParams) : self::SCRUBBED;
        }

        return $inputs;
    }

    /**
     * Scrub sensitive information from request headers.
     *
     * The value of a header whose name contains one of the configured parts is replaced,
     * so the report still shows the header was sent. Query parameters in a Referer are
     * scrubbed like inputs.
     *
     * @param array<array<mixed>> $headers The original headers
     *
     * @return array<array<mixed>> The scrubbed headers
     */
    public function scrubRequestHeaders(array $headers): array
    {
        foreach ($headers as $name => $values)
        {
            $name = (string) $name;

            if ($this->containsAny($name, $this->scrubHeaders))
            {
                $headers[$name] = [self::SCRUBBED];

                continue;
            }

            if (strtolower($name) === 'referer')
            {
                $headers[$name] = array_map(fn ($value) => is_string($value) ? $this->scrubUrl($value) : $value, $values);
            }
        }

        return $headers;
    }

    /**
     * Scrub sensitive information from request inputs.
     *
     * The value of an input whose key contains one of the configured parts is replaced,
     * at any depth, so the report still shows the input was sent.
     *
     * @param array<mixed> $inputs The original inputs
     *
     * @return array<mixed> The scrubbed inputs
     */
    public function scrubInputs(array $inputs): array
    {
        foreach ($inputs as $key => $value)
        {
            if (is_string($key) && $this->containsAny($key, $this->scrubInputs))
            {
                $inputs[$key] = self::SCRUBBED;
            }
            elseif (is_array($value))
            {
                $inputs[$key] = $this->scrubInputs($value);
            }
        }

        return $inputs;
    }

    /**
     * Reduce the server params to the ones that are allowed in a report.
     *
     * Server params can hold credentials and the environment of the process, under names
     * that cannot be known in advance. So only the configured params are kept. Query
     * parameters in the kept URLs are scrubbed like inputs.
     *
     * @param array<mixed> $serverParams The original server params
     *
     * @return array<mixed> The allowed server params
     */
    public function scrubServerParams(array $serverParams): array
    {
        $allowed = [];

        foreach ($serverParams as $name => $value)
        {
            if (!in_array($name, $this->serverParams, true))
            {
                continue;
            }

            if (is_string($value))
            {
                if ($name === 'QUERY_STRING')
                {
                    $value = $this->scrubQueryString($value);
                }
                elseif ($name === 'REQUEST_URI' || $name === 'HTTP_REFERER')
                {
                    $value = $this->scrubUrl($value);
                }
            }

            $allowed[$name] = $value;
        }

        return $allowed;
    }

    /**
     * Scrub the query parameters of a URL.
     */
    private function scrubUrl(string $url): string
    {
        $fragment = '';
        $fragmentPosition = strpos($url, '#');

        if ($fragmentPosition !== false)
        {
            $fragment = substr($url, $fragmentPosition);
            $url = substr($url, 0, $fragmentPosition);
        }

        $queryPosition = strpos($url, '?');

        if ($queryPosition === false)
        {
            return $url.$fragment;
        }

        $query = $this->scrubQueryString(substr($url, $queryPosition + 1));

        return substr($url, 0, $queryPosition + 1).$query.$fragment;
    }

    /**
     * Scrub the parameters of a query string. The string is only rewritten when it
     * contains something to scrub.
     */
    private function scrubQueryString(string $query): string
    {
        $params = [];
        parse_str($query, $params);

        $scrubbed = $this->scrubInputs($params);

        if ($scrubbed === $params)
        {
            return $query;
        }

        return http_build_query($scrubbed);
    }

    /**
     * Check if a name contains one of the given parts, ignoring case.
     *
     * @param array<string> $parts
     */
    private function containsAny(string $name, array $parts): bool
    {
        $name = strtolower($name);

        foreach ($parts as $part)
        {
            if ($part !== '' && str_contains($name, strtolower($part)))
            {
                return true;
            }
        }

        return false;
    }
}
