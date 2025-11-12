<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use WebFramework\Core\RuntimeEnvironment;
use WebFramework\Database\Database;
use WebFramework\Database\DatabaseProvider;
use WebFramework\Diagnostics\DebugService;
use WebFramework\Diagnostics\MailReportFunction;
use WebFramework\Diagnostics\NullReportFunction;
use WebFramework\Entity\User;
use WebFramework\Security\NullAuthenticationService;
use WebFramework\Support\ErrorReport;

/**
 * @internal
 *
 * @covers \WebFramework\Diagnostics\DebugService
 */
final class DebugServiceTest extends Unit
{
    public function testGenerateHash()
    {
        $errorReport = $this->make(ErrorReport::class, [
            'serverName' => 'ServerName',
            'requestSource' => 'RequestSource',
            'file' => 'File',
            'line' => 1,
            'message' => 'Message',
            'errorType' => 'ErrorType',
        ]);

        verify($errorReport->getHash())
            ->equals('04f41ef755efe7ddef652fa8d5d95cb26b872ead')
        ;
    }

    public function testGetDatabaseErrorNull()
    {
        $instance = $this->makeEmptyExcept(DebugService::class, 'getDatabaseError');

        verify($instance->getDatabaseError(null))
            ->equals('Not initialized yet')
        ;
    }

    public function testGetDatabaseErrorNoError()
    {
        $instance = $this->make(DebugService::class);
        $database = $this->makeEmpty(Database::class, ['getLastError' => '']);

        verify($instance->getDatabaseError($database))
            ->equals('None')
        ;
    }

    public function testGetDatabaseErrorError()
    {
        $instance = $this->make(DebugService::class);
        $database = $this->makeEmpty(Database::class, ['getLastError' => 'DB ERROR']);

        verify($instance->getDatabaseError($database))
            ->equals('DB ERROR')
        ;
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
            ->equals(null)
        ;
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
            ->equals([
                'user_id' => 1,
                'username' => 'TestUser',
                'email' => 'TestEmail',
            ])
        ;
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
            ->equals($traceFiltered)
        ;
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
                'class' => 'DebugService',
                'function' => 'getReport',
                'args' => 'report_args',
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
                'class' => 'DebugService',
                'function' => 'getReport',
                'args' => 'report_args',
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
            ->equals($traceFiltered)
        ;
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
            ->equals($trace)
        ;
    }

    public function testFilterTraceScrub()
    {
        $trace = [
            [
                'class' => 'Object2',
                'function' => 'function2',
                'extra' => [
                    'database' => 'scrubbed',
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

        $traceFiltered = [
            [
                'class' => 'Object2',
                'function' => 'function2',
                'extra' => 'extra1',
                'extra' => [
                    'database' => 'scrubbed',
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
            ->equals($traceFiltered)
        ;
    }

    public function testCondenseStack()
    {
        $trace = [
            [
                'file' => '/app/src/Dir2/File2.php',
                'line' => '10',
                'class' => 'Class2',
                'type' => '::',
                'function' => 'function2',
            ],
            [
                'file' => '/app/src/File1.php',
                'line' => '11',
                'function' => 'function1',
            ],
        ];

        $condensedStack = [
            'Class2::function2() (/src/Dir2/File2.php:10)',
            'function1() (/src/File1.php:11)',
        ];

        $instance = $this->make(
            DebugService::class,
            [
                'runtimeEnvironment' => $this->makeEmpty(
                    RuntimeEnvironment::class,
                    [
                        'getAppDir' => '/app',
                    ],
                ),
            ]
        );

        verify($instance->condenseStack($trace))
            ->equals($condensedStack)
        ;
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
            ->equals($headersScrubbed)
        ;
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
            ->equals($headersScrubbed)
        ;
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
            ->equals([])
        ;
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

        verify($instance->getInputsReport($request))
            ->equals([
                'get' => $queryParams,
            ])
        ;
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

        verify($instance->getInputsReport($request))
            ->equals([
                'post' => $postParams,
            ])
        ;
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

        verify($instance->getInputsReport($request))
            ->equals([
                'json_data' => $postParams,
            ])
        ;
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

        verify($instance->getInputsReport($request))
            ->equals([
                'json_parsing_failed' => $badJson,
            ])
        ;
    }

    public function testErrorReportEmpty()
    {
        $instance = $this->make(
            ErrorReport::class,
            [
            ]
        );

        $reportFmt = <<<'TXT'
File: unknown
Line: 0
ErrorType: unknown
Message: unknown

Server: unknown
Request: unknown

Condensed backtrace:
No stack trace available

Last Database error:
Not initialized yet

Inputs:
Array
(
)

Auth:
Not authenticated

Headers:
Array
(
)

Server:
Array
(
)

TXT;
        $report = $instance->toString();

        verify($report)
            ->equals($reportFmt)
        ;
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
                'reportFunction' => $this->makeEmpty(NullReportFunction::class),
                'runtimeEnvironment' => $this->makeEmpty(
                    RuntimeEnvironment::class,
                    [
                        'getServerName' => 'TestServer',
                    ]
                ),
            ],
        );

        $database = $this->makeEmpty(Database::class, ['getLastError' => 'DB ERROR']);
        $databaseProvider->set($database);

        $requestFactory = new RequestFactory();
        $request = $requestFactory->createRequest('GET', 'https://test.com/test-url');

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

        $reportFmt = <<<'TXT'
File: Object2.php
Line: 3
ErrorType: test
Message: TestMessage

Server: TestServer
Request: GET /test-url

Condensed backtrace:
Object2->function2() (Object2.php:3)
Object1->function1() (Object1.php:4)

Last Database error:
DB ERROR

Inputs:
Array
(
)

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

        verify($report->toString())
            ->equals($reportFmt)
        ;
    }

    public function testReportError()
    {
        $instance = $this->construct(
            DebugService::class,
            [
                'authenticationService' => $this->makeEmpty(NullAuthenticationService::class),
                'databaseProvider' => $this->makeEmpty(DatabaseProvider::class),
                'reportFunction' => $this->makeEmpty(
                    MailReportFunction::class,
                    [
                        'report' => Expected::once(),
                    ]
                ),
                'runtimeEnvironment' => $this->makeEmpty(
                    RuntimeEnvironment::class,
                    [
                        'getServerName' => 'TestServer',
                    ]
                ),
            ],
            [
                'getErrorReport' => $this->make(ErrorReport::class, [
                    'toString' => 'my_report',
                ]),
            ]
        );

        verify($instance->reportError('TestMessage'));
    }
}
