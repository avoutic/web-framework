<?php

namespace Tests\Unit;

use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Request;
use WebFramework\Core\Database;
use WebFramework\Core\DebugService;
use WebFramework\Core\WF;

/**
 * @internal
 *
 * @coversNothing
 */
final class DebugServiceTest extends \Codeception\Test\Unit
{
    public function testGenerateHash()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->generate_hash('ServerName', 'RequestSource', 'File', 1, 'Message'))
            ->equals('5bd8c554bf94a90d97e9f31c63b69fde17ac4bb9');
    }

    public function testGenerateHashNoServerName()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->generate_hash('', 'RequestSource', 'File', 1, 'Message'))
            ->equals('bf0a0f18bf3c2e20ea0fe62092376caccefcbb7a');
    }

    public function testGenerateHashNoRequestSource()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->generate_hash('ServerName', '', 'File', 1, 'Message'))
            ->equals('bf0f9b3a2f36e3e1a8359c289494a41a3c673c45');
    }

    public function testGenerateHashNoFile()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->generate_hash('ServerName', 'RequestSource', 'unknown', 1, 'Message'))
            ->equals('8bdac8c96d84253c28d5981d096550747be0d2f8');
    }

    public function testGenerateHasNoLine()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->generate_hash('ServerName', 'RequestSource', 'File', 0, 'Message'))
            ->equals('72a2066e2671474739c142d668a25da79b8184a0');
    }

    public function testGenerateHashNoMessage()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->generate_hash('ServerName', 'RequestSource', 'File', 1, ''))
            ->equals('2d63a5522289108ab2091fd5b99f0ba0499bff76');
    }

    public function testGetDatabaseErrorNull()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->get_database_error(null))
            ->equals('Not initialized yet');
    }

    public function testGetDatabaseErrorNoError()
    {
        $framework = $this->makeEmpty(WF::class);
        $database = $this->makeEmpty(Database::class, ['get_last_error' => '']);

        $instance = new DebugService($framework, '');
        $instance->set_database($database);

        verify($instance->get_database_error($database))
            ->equals('None');
    }

    public function testGetDatabaseErrorError()
    {
        $framework = $this->makeEmpty(WF::class);
        $database = $this->makeEmpty(Database::class, ['get_last_error' => 'DB ERROR']);

        $instance = new DebugService($framework, '');
        $instance->set_database($database);

        verify($instance->get_database_error($database))
            ->equals('DB ERROR');
    }

    public function testGetAuthenticationStatusNone()
    {
        $framework = $this->makeEmpty(WF::class, ['is_authenticated' => false]);

        $instance = new DebugService($framework, '');

        verify($instance->get_authentication_status())
            ->equals("Not authenticated\n");
    }

    public function testGetAuthenticationStatusSimple()
    {
        $auth_data = ['var1' => 'val1', 'var2' => 'val2'];
        $framework = $this->makeEmpty(WF::class, ['is_authenticated' => true, 'get_authenticated' => $auth_data]);

        $instance = new DebugService($framework, '');

        verify($instance->get_authentication_status())
            ->equals(print_r($auth_data, true));
    }

    public function testGetAuthenticationStatusScrubbed()
    {
        $auth_data = [
            'var1' => 'val1',
            'database' => 'val2',
            'databases' => 'val3',
            'config' => 'val4',
            'recursive' => [
                'regular' => 'val5',
                'config' => 'val6',
            ],
        ];

        $auth_scrubbed = [
            'var1' => 'val1',
            'database' => 'scrubbed',
            'databases' => 'scrubbed',
            'config' => 'scrubbed',
            'recursive' => [
                'regular' => 'val5',
                'config' => 'scrubbed',
            ],
        ];

        $framework = $this->makeEmpty(WF::class, ['is_authenticated' => true, 'get_authenticated' => $auth_data]);

        $instance = new DebugService($framework, '');

        verify($instance->get_authentication_status())
            ->equals(print_r($auth_scrubbed, true));
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

        $trace_filtered = [
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

        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->filter_trace($trace, true, false))
            ->equals($trace_filtered);
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

        $trace_filtered = [
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

        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->filter_trace($trace, true, false))
            ->equals($trace_filtered);
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

        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->filter_trace($trace, false, false))
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

        $trace_filtered = [
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

        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->filter_trace($trace, false, true))
            ->equals($trace_filtered);
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

        $trace_filtered = [
            [
                'class' => 'Object2',
                'function' => 'exit_send_error',
            ],
            [
                'class' => 'Object1',
                'function' => 'exit_error',
            ],
        ];

        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->filter_trace($trace, false, false))
            ->equals($trace_filtered);
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

        $condensed_stack = <<<'TXT'
File2(10): Class2::function2()
File1(11): function1()

TXT;

        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->condense_stack($trace))
            ->equals($condensed_stack);
    }

    public function testScrubRequestHeaders()
    {
        $headers = [
            'key1' => 'val1',
            'key2' => 'val2',
            'cookie' => 'val3',
            'key3' => 'val4',
        ];

        $headers_scrubbed = [
            'key1' => 'val1',
            'key2' => 'val2',
            'key3' => 'val4',
        ];

        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->scrub_request_headers($headers))
            ->equals($headers_scrubbed);
    }

    public function testScrubRequestHeadersCaseInsensitive()
    {
        $headers = [
            'key1' => 'val1',
            'key2' => 'val2',
            'COOKIE' => 'val3',
            'key3' => 'val4',
        ];

        $headers_scrubbed = [
            'key1' => 'val1',
            'key2' => 'val2',
            'key3' => 'val4',
        ];

        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        verify($instance->scrub_request_headers($headers))
            ->equals($headers_scrubbed);
    }

    public function testGetInputsReportEmpty()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        $request = $this->makeEmpty(Request::class, [
            'getQueryParams' => [],
            'getHeaderLine' => 'Content-Type: text',
            'getBody' => '',
            'getParsedBody' => null,
        ]);

        verify($instance->get_inputs_report($request))
            ->equals("No inputs\n");
    }

    public function testGetInputsReportJustGet()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        $query_params = ['key1' => 'val1', 'key2' => 'val2'];

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', 'https://test.com');

        $request = $request
            ->withQueryParams($query_params)
        ;

        $get_fmt = print_r($query_params, true);

        $inputs_fmt = <<<TXT
GET:
{$get_fmt}

TXT;
        verify($instance->get_inputs_report($request))
            ->equals($inputs_fmt);
    }

    public function testGetInputsReportJustPost()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        $post_params = ['key1' => 'val1', 'key2' => 'val2'];

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('POST', 'https://test.com');

        $request = $request
            ->withParsedBody($post_params)
        ;

        $post_fmt = print_r($post_params, true);

        $inputs_fmt = <<<TXT
POST:
{$post_fmt}

TXT;
        verify($instance->get_inputs_report($request))
            ->equals($inputs_fmt);
    }

    public function testGetInputsReportJustJson()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        $post_params = ['key1' => 'val1', 'key2' => 'val2'];

        $stream_factory = new StreamFactory();
        $request_factory = new RequestFactory($stream_factory);

        $stream = $stream_factory->createStream(json_encode($post_params));
        $request = $request_factory->createRequest('POST', 'https://test.com');

        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream)
        ;

        $json_fmt = print_r($post_params, true);

        $inputs_fmt = <<<TXT
JSON data:
{$json_fmt}

TXT;
        verify($instance->get_inputs_report($request))
            ->equals($inputs_fmt);
    }

    public function testGetInputsReportBadJson()
    {
        $framework = $this->makeEmpty(WF::class);

        $instance = new DebugService($framework, '');

        $bad_json = '{"key1" : "val1","key2":"val2"';

        $stream_factory = new StreamFactory();
        $request_factory = new RequestFactory($stream_factory);

        $stream = $stream_factory->createStream($bad_json);
        $request = $request_factory->createRequest('POST', 'https://test.com');

        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream)
        ;

        $inputs_fmt = <<<TXT
JSON parsing failed:
{$bad_json}

TXT;
        verify($instance->get_inputs_report($request))
            ->equals($inputs_fmt);
    }

    public function testErrorReportEmpty()
    {
        $instance = $this->construct(
            DebugService::class,
            [
                'framework' => $this->makeEmpty(WF::class),
                'server_name' => 'TestServer',
            ],
            [
                'generate_hash' => 'my_hash',
            ]
        );

        $low_info_report_fmt = <<<'TXT'
File: unknown
Line: 0

TXT;

        $report_fmt = <<<'TXT'
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

Backtrace:
No stack

Headers:
No request

Server:
No request

TXT;
        $report = $instance->get_error_report([], null, 'test', 'TestMessage');

        verify($report['hash'])
            ->equals('my_hash');
        verify($report['low_info_message'])
            ->equals($low_info_report_fmt);
        verify($report['message'])
            ->equals($report_fmt);
    }

    public function testErrorReportContent()
    {
        $instance = $this->construct(
            DebugService::class,
            [
                'framework' => $this->makeEmpty(
                    WF::class,
                    [
                        'is_authenticated' => true,
                        'get_authenticated' => ['var1' => 'val1', 'var2' => 'val2'],
                    ],
                ),
                'server_name' => 'TestServer',
            ],
            [
                'generate_hash' => 'my_hash',
            ]
        );

        $database = $this->makeEmpty(Database::class, ['get_last_error' => 'DB ERROR']);
        $instance->set_database($database);

        $request_factory = new RequestFactory();
        $request = $request_factory->createRequest('GET', 'https://test.com');

        $trace = [
            [
                'file' => 'DebugService.php',
                'line' => 1,
                'class' => 'DebugService',
                'type' => '->',
                'function' => 'filter_trace',
                'args' => 'filter_args',
                'extra' => 'extra1',
            ],
            [
                'file' => 'AssertService.php',
                'line' => 2,
                'class' => 'FrameworkAssertService',
                'type' => '->',
                'function' => 'verify',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
            [
                'file' => 'Object2.php',
                'line' => 3,
                'class' => 'Object2',
                'type' => '->',
                'function' => 'function2',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
            [
                'file' => 'Object1.php',
                'line' => 4,
                'class' => 'Object1',
                'type' => '->',
                'function' => 'function1',
                'args' => 'verify_args',
                'extra' => 'extra1',
            ],
        ];

        $low_info_report_fmt = <<<'TXT'
File: Object2.php
Line: 3

TXT;

        $report_fmt = <<<'TXT'
File: Object2.php
Line: 3
ErrorType: test
Message: TestMessage

Server: TestServer
Request: GET https://test.com

Condensed backtrace:
Object2.php(3): Object2->function2()
Object1.php(4): Object1->function1()

Last Database error:
DB ERROR

Inputs:
No inputs

Auth:
Array
(
    [var1] => val1
    [var2] => val2
)

Backtrace:
Array
(
    [0] => Array
        (
            [file] => Object2.php
            [line] => 3
            [class] => Object2
            [type] => ->
            [function] => function2
            [args] => verify_args
            [extra] => extra1
        )

    [1] => Array
        (
            [file] => Object1.php
            [line] => 4
            [class] => Object1
            [type] => ->
            [function] => function1
            [args] => verify_args
            [extra] => extra1
        )

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
        $report = $instance->get_error_report($trace, $request, 'test', 'TestMessage');

        verify($report['hash'])
            ->equals('my_hash');
        verify($report['low_info_message'])
            ->equals($low_info_report_fmt);
        verify($report['message'])
            ->equals($report_fmt);
    }
}
