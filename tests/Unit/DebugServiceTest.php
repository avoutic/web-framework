<?php

namespace Tests\Unit;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use WebFramework\Core\Database;
use WebFramework\Core\DatabaseProvider;
use WebFramework\Core\DebugService;
use WebFramework\Entity\User;
use WebFramework\Security\NullAuthenticationService;

/**
 * @internal
 *
 * @coversNothing
 */
final class DebugServiceTest extends \Codeception\Test\Unit
{
    public function testGenerateHash()
    {
        $instance = $this->makeEmptyExcept(DebugService::class, 'generateHash');

        verify($instance->generateHash('ServerName', 'RequestSource', 'File', 1, 'Message'))
            ->equals('5bd8c554bf94a90d97e9f31c63b69fde17ac4bb9');
    }

    public function testGenerateHashNoServerName()
    {
        $instance = $this->makeEmptyExcept(DebugService::class, 'generateHash');

        verify($instance->generateHash('', 'RequestSource', 'File', 1, 'Message'))
            ->equals('bf0a0f18bf3c2e20ea0fe62092376caccefcbb7a');
    }

    public function testGenerateHashNoRequestSource()
    {
        $instance = $this->makeEmptyExcept(DebugService::class, 'generateHash');

        verify($instance->generateHash('ServerName', '', 'File', 1, 'Message'))
            ->equals('bf0f9b3a2f36e3e1a8359c289494a41a3c673c45');
    }

    public function testGenerateHashNoFile()
    {
        $instance = $this->makeEmptyExcept(DebugService::class, 'generateHash');

        verify($instance->generateHash('ServerName', 'RequestSource', 'unknown', 1, 'Message'))
            ->equals('8bdac8c96d84253c28d5981d096550747be0d2f8');
    }

    public function testGenerateHasNoLine()
    {
        $instance = $this->makeEmptyExcept(DebugService::class, 'generateHash');

        verify($instance->generateHash('ServerName', 'RequestSource', 'File', 0, 'Message'))
            ->equals('72a2066e2671474739c142d668a25da79b8184a0');
    }

    public function testGenerateHashNoMessage()
    {
        $instance = $this->makeEmptyExcept(DebugService::class, 'generateHash');

        verify($instance->generateHash('ServerName', 'RequestSource', 'File', 1, ''))
            ->equals('2d63a5522289108ab2091fd5b99f0ba0499bff76');
    }

    public function testGetDatabaseErrorNull()
    {
        $instance = $this->makeEmptyExcept(DebugService::class, 'getDatabaseError');

        verify($instance->getDatabaseError(null))
            ->equals('Not initialized yet');
    }

    public function testGetDatabaseErrorNoError()
    {
        $instance = $this->make(DebugService::class);
        $database = $this->makeEmpty(Database::class, ['getLastError' => '']);

        verify($instance->getDatabaseError($database))
            ->equals('None');
    }

    public function testGetDatabaseErrorError()
    {
        $instance = $this->make(DebugService::class);
        $database = $this->makeEmpty(Database::class, ['getLastError' => 'DB ERROR']);

        verify($instance->getDatabaseError($database))
            ->equals('DB ERROR');
    }

    public function testGetAuthenticationStatusNone()
    {
        $instance = $this->make(
            DebugService::class,
            [
                'authenticationService' => $this->makeEmpty(
                    NullAuthenticationService::class,
                    [
                        'isAuthenticated' => false,
                    ]
                ),
            ]
        );

        verify($instance->getAuthenticationStatus())
            ->equals("Not authenticated\n");
    }

    public function testGetAuthenticationStatusSimple()
    {
        $userData = ['id' => 1, 'username' => 'TestUser', 'email' => 'TestEmail'];
        $authData = ['user_id' => 1, 'username' => 'TestUser', 'email' => 'TestEmail'];

        $instance = $this->make(
            DebugService::class,
            [
                'authenticationService' => $this->makeEmpty(
                    NullAuthenticationService::class,
                    [
                        'isAuthenticated' => true,
                        'getAuthenticatedUser' => $this->make(
                            User::class,
                            $userData,
                        ),
                    ]
                ),
            ]
        );

        verify($instance->getAuthenticationStatus())
            ->equals(print_r($authData, true));
    }

    public function testFilterTraceSkipStart()
    {
        $trace = [
            [
                'class' => 'DebugService',
                'function' => 'filter_trace',
                'args' => 'filter_args',
                'extra' => 'extra1',
            ],
            [
                'class' => 'FrameworkAssertService',
                'function' => 'verify',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
            [
                'class' => 'Object2',
                'function' => 'function2',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
            [
                'class' => 'Object1',
                'function' => 'function1',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
        ];

        $traceFiltered = [
            [
                'class' => 'Object2',
                'function' => 'function2',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
            [
                'class' => 'Object1',
                'function' => 'function1',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
        ];

        $instance = $this->make(DebugService::class);

        verify($instance->filterTrace($trace, true, false))
            ->equals($traceFiltered);
    }

    public function testFilterTraceLeaveMiddle()
    {
        $trace = [
            [
                'class' => 'DebugService',
                'function' => 'filter_trace',
                'args' => 'filter_args',
                'extra' => 'extra1',
            ],
            [
                'class' => 'Object2',
                'function' => 'function2',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
            [
                'class' => 'FrameworkAssertService',
                'function' => 'verify',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
            [
                'class' => 'Object1',
                'function' => 'function1',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
        ];

        $traceFiltered = [
            [
                'class' => 'Object2',
                'function' => 'function2',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
            [
                'class' => 'FrameworkAssertService',
                'function' => 'verify',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
            [
                'class' => 'Object1',
                'function' => 'function1',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
        ];

        $instance = $this->make(DebugService::class);

        verify($instance->filterTrace($trace, true, false))
            ->equals($traceFiltered);
    }

    public function testFilterTraceNoSkip()
    {
        $trace = [
            [
                'class' => 'DebugService',
                'function' => 'filter_trace',
                'args' => 'filter_args',
            ],
            [
                'class' => 'FrameworkAssertService',
                'function' => 'verify',
                'args' => 'verify_args',
            ],
            [
                'class' => 'Object2',
                'function' => 'function2',
                'args' => 'verify_args',
            ],
            [
                'class' => 'Object1',
                'function' => 'function1',
                'args' => 'verify_args',
            ],
        ];

        $instance = $this->make(DebugService::class);

        verify($instance->filterTrace($trace, false, false))
            ->equals($trace);
    }

    public function testFilterTraceScrub()
    {
        $trace = [
            [
                'class' => 'Object2',
                'function' => 'function2',
                'extra' => [
                    'database' => 'data',
                    'databases' => 'data',
                    'config' => 'data',
                    'regular' => 'normal',
                ],
            ],
            [
                'class' => 'Object1',
                'function' => 'function1',
                'extra' => 'extra1',
            ],
        ];

        $traceFiltered = [
            [
                'class' => 'Object2',
                'function' => 'function2',
                'extra' => 'extra1',
                'extra' => [
                    'database' => 'scrubbed',
                    'databases' => 'scrubbed',
                    'config' => 'scrubbed',
                    'regular' => 'normal',
                ],
            ],
            [
                'class' => 'Object1',
                'function' => 'function1',
                'extra' => 'extra1',
            ],
        ];

        $instance = $this->make(DebugService::class);

        verify($instance->filterTrace($trace, false, true))
            ->equals($traceFiltered);
    }

    public function testFilterTraceUnsetExitArgs()
    {
        $trace = [
            [
                'class' => 'Object2',
                'function' => 'exit_send_error',
                'args' => 'verify_args',
            ],
            [
                'class' => 'Object1',
                'function' => 'exit_error',
                'args' => 'verify_args',
            ],
        ];

        $traceFiltered = [
            [
                'class' => 'Object2',
                'function' => 'exit_send_error',
            ],
            [
                'class' => 'Object1',
                'function' => 'exit_error',
            ],
        ];

        $instance = $this->make(DebugService::class);

        verify($instance->filterTrace($trace, false, false))
            ->equals($traceFiltered);
    }

    public function testCondenseStack()
    {
        $trace = [
            [
                'file' => 'File2',
                'line' => '10',
                'class' => 'Class2',
                'type' => '::',
                'function' => 'function2',
            ],
            [
                'file' => 'File1',
                'line' => '11',
                'function' => 'function1',
            ],
        ];

        $condensedStack = <<<'TXT'
File2(10): Class2::function2()
File1(11): function1()

TXT;

        $instance = $this->make(DebugService::class);

        verify($instance->condenseStack($trace))
            ->equals($condensedStack);
    }

    public function testScrubRequestHeaders()
    {
        $headers = [
            'key1' => 'val1',
            'key2' => 'val2',
            'cookie' => 'val3',
            'key3' => 'val4',
        ];

        $headersScrubbed = [
            'key1' => 'val1',
            'key2' => 'val2',
            'key3' => 'val4',
        ];

        $instance = $this->make(DebugService::class);

        verify($instance->scrubRequestHeaders($headers))
            ->equals($headersScrubbed);
    }

    public function testScrubRequestHeadersCaseInsensitive()
    {
        $headers = [
            'key1' => 'val1',
            'key2' => 'val2',
            'COOKIE' => 'val3',
            'key3' => 'val4',
        ];

        $headersScrubbed = [
            'key1' => 'val1',
            'key2' => 'val2',
            'key3' => 'val4',
        ];

        $instance = $this->make(DebugService::class);

        verify($instance->scrubRequestHeaders($headers))
            ->equals($headersScrubbed);
    }

    public function testGetInputsReportEmpty()
    {
        $instance = $this->make(DebugService::class);

        $request = $this->makeEmpty(Request::class, [
            'getQueryParams' => [],
            'getHeaderLine' => 'Content-Type: text',
            'getBody' => '',
            'getParsedBody' => null,
        ]);

        verify($instance->getInputsReport($request))
            ->equals("No inputs\n");
    }

    public function testGetInputsReportJustGet()
    {
        $instance = $this->make(DebugService::class);

        $queryParams = ['key1' => 'val1', 'key2' => 'val2'];

        $requestFactory = new RequestFactory();
        $request = $requestFactory->createRequest('GET', 'https://test.com');

        $request = $request
            ->withQueryParams($queryParams)
        ;

        $getFmt = print_r($queryParams, true);

        $inputsFmt = <<<TXT
GET:
{$getFmt}

TXT;
        verify($instance->getInputsReport($request))
            ->equals($inputsFmt);
    }

    public function testGetInputsReportJustPost()
    {
        $instance = $this->make(DebugService::class);

        $postParams = ['key1' => 'val1', 'key2' => 'val2'];

        $requestFactory = new RequestFactory();
        $request = $requestFactory->createRequest('POST', 'https://test.com');

        $request = $request
            ->withParsedBody($postParams)
        ;

        $postFmt = print_r($postParams, true);

        $inputsFmt = <<<TXT
POST:
{$postFmt}

TXT;
        verify($instance->getInputsReport($request))
            ->equals($inputsFmt);
    }

    public function testGetInputsReportJustJson()
    {
        $instance = $this->make(DebugService::class);

        $postParams = ['key1' => 'val1', 'key2' => 'val2'];

        $streamFactory = new StreamFactory();
        $requestFactory = new RequestFactory($streamFactory);

        $stream = $streamFactory->createStream(json_encode($postParams));
        $request = $requestFactory->createRequest('POST', 'https://test.com');

        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream)
        ;

        $jsonFmt = print_r($postParams, true);

        $inputsFmt = <<<TXT
JSON data:
{$jsonFmt}

TXT;
        verify($instance->getInputsReport($request))
            ->equals($inputsFmt);
    }

    public function testGetInputsReportBadJson()
    {
        $instance = $this->make(DebugService::class);

        $badJson = '{"key1" : "val1","key2":"val2"';

        $streamFactory = new StreamFactory();
        $requestFactory = new RequestFactory($streamFactory);

        $stream = $streamFactory->createStream($badJson);
        $request = $requestFactory->createRequest('POST', 'https://test.com');

        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream)
        ;

        $inputsFmt = <<<TXT
JSON parsing failed:
{$badJson}

TXT;
        verify($instance->getInputsReport($request))
            ->equals($inputsFmt);
    }

    public function testErrorReportEmpty()
    {
        $instance = $this->construct(
            DebugService::class,
            [
                'authenticationService' => $this->makeEmpty(NullAuthenticationService::class),
                'databaseProvider' => $this->makeEmpty(DatabaseProvider::class),
                'appDir' => '',
                'serverName' => 'TestServer',
            ],
            [
                'generateHash' => 'my_hash',
            ]
        );

        $lowInfoReportFmt = <<<'TXT'
An error occurred.

TXT;

        $reportFmt = <<<'TXT'
File: unknown
Line: 0
ErrorType: test
Message: TestMessage

Server: TestServer
Request: app

Condensed backtrace:

Last Database error:
Not initialized yet

Inputs:
No request

Auth:
Not authenticated

Headers:
No request

Server:
No request

TXT;
        $report = $instance->getErrorReport([], null, 'test', 'TestMessage');

        verify($report['hash'])
            ->equals('my_hash');
        verify($report['low_info_message'])
            ->equals($lowInfoReportFmt);
        verify($report['message'])
            ->equals($reportFmt);
    }

    public function testErrorReportContent()
    {
        $userData = ['id' => 1, 'username' => 'TestUser', 'email' => 'TestEmail'];

        $databaseProvider = $this->make(
            DatabaseProvider::class,
        );

        $instance = $this->construct(
            DebugService::class,
            [
                'authenticationService' => $this->makeEmpty(
                    NullAuthenticationService::class,
                    [
                        'isAuthenticated' => true,
                        'getAuthenticatedUser' => $this->make(
                            User::class,
                            $userData,
                        ),
                    ],
                ),
                'databaseProvider' => $databaseProvider,
                'appDir' => '',
                'serverName' => 'TestServer',
            ],
            [
                'generateHash' => 'my_hash',
            ]
        );

        $database = $this->makeEmpty(Database::class, ['getLastError' => 'DB ERROR']);
        $databaseProvider->set($database);

        $requestFactory = new RequestFactory();
        $request = $requestFactory->createRequest('GET', 'https://test.com');

        $trace = [
            [
                'file' => 'DebugService.php',
                'line' => 1,
                'class' => 'DebugService',
                'type' => '->',
                'function' => 'filter_trace',
                'args' => ['filter_arg' => 'val'],
                'extra' => 'extra1',
            ],
            [
                'file' => 'AssertService.php',
                'line' => 2,
                'class' => 'FrameworkAssertService',
                'type' => '->',
                'function' => 'verify',
                'args' => ['verify_arg' => 'val'],
                'extra' => 'extra1',
            ],
            [
                'file' => 'Object2.php',
                'line' => 3,
                'class' => 'Object2',
                'type' => '->',
                'function' => 'function2',
                'args' => ['func2_arg' => 'val'],
                'extra' => 'extra1',
            ],
            [
                'file' => 'Object1.php',
                'line' => 4,
                'class' => 'Object1',
                'type' => '->',
                'function' => 'function1',
                'args' => ['func1_arg' => 'val'],
                'extra' => 'extra1',
            ],
        ];

        $lowInfoReportFmt = <<<'TXT'
An error occurred.

TXT;

        $reportFmt = <<<'TXT'
File: Object2.php
Line: 3
ErrorType: test
Message: TestMessage

Server: TestServer
Request: GET https://test.com

Condensed backtrace:
Object2.php(3): Object2->function2()
Array
(
    [func2_arg] => val
)

Object1.php(4): Object1->function1()
Array
(
    [func1_arg] => val
)


Last Database error:
DB ERROR

Inputs:
No inputs

Auth:
Array
(
    [user_id] => 1
    [username] => TestUser
    [email] => TestEmail
)

Headers:
Array
(
    [Host] => Array
        (
            [0] => test.com
        )

)

Server:
Array
(
)

TXT;
        $report = $instance->getErrorReport($trace, $request, 'test', 'TestMessage');

        verify($report['hash'])
            ->equals('my_hash');
        verify($report['low_info_message'])
            ->equals($lowInfoReportFmt);
        verify($report['message'])
            ->equals($reportFmt);
    }
}
