<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WebFramework\Cache\NullCache;
use WebFramework\Diagnostics\MailReportFunction;
use WebFramework\Mail\NullMailService;
use WebFramework\Support\ErrorReport;

/**
 * @internal
 *
 * @covers \WebFramework\Diagnostics\MailReportFunction
 */
final class MailReportFunctionTest extends Unit
{
    public function testReportUncached()
    {
        $stored = false;

        $instance = $this->construct(
            MailReportFunction::class,
            [
                $this->makeEmpty(
                    NullCache::class,
                    [
                        // Read the counter, and read it back after storing the first occurrence
                        'get' => Expected::exactly(2, function () use (&$stored) {
                            return $stored;
                        }),
                        'set' => Expected::once(function ($path, $value, $expiresAfter) use (&$stored) {
                            // A number of seconds, not a timestamp: the counter covers ten minutes
                            verify($expiresAfter)->same(600);
                            $stored = $value;
                        }),
                    ]
                ),
                $this->makeEmpty(LoggerInterface::class, [
                    'warning' => Expected::never(),
                ]),
                $this->makeEmpty(
                    NullMailService::class,
                    [
                        'sendRawMail' => Expected::once(true),
                    ]
                ),
                '',
            ],
        );

        $report = $this->make(ErrorReport::class, [
            'toString' => 'my_report',
            'getHash' => 'my_hash',
        ]);

        verify($instance->report('TestMessage', 'TestError', $report));
    }

    public function testWarnsAndStillSendsWhenCacheDoesNotStore()
    {
        $instance = $this->construct(
            MailReportFunction::class,
            [
                new NullCache(),
                $this->makeEmpty(LoggerInterface::class, [
                    'warning' => Expected::once(function ($message) {
                        verify($message)->stringContainsString('rate limiting is not active');
                    }),
                ]),
                $this->makeEmpty(
                    NullMailService::class,
                    [
                        'sendRawMail' => Expected::once(true),
                    ]
                ),
                '',
            ],
        );

        $report = $this->make(ErrorReport::class, [
            'toString' => 'my_report',
            'getHash' => 'my_hash',
        ]);

        $instance->report('TestMessage', 'TestError', $report);
    }

    public function testMailDebugInfoCached2()
    {
        $instance = $this->construct(
            MailReportFunction::class,
            [
                $this->makeEmpty(
                    NullCache::class,
                    [
                        'get' => Expected::once(['count' => 2]),
                        'set' => Expected::once(),
                    ]
                ),
                $this->makeEmpty(LoggerInterface::class),
                $this->makeEmpty(
                    NullMailService::class,
                    [
                        'send_raw' => Expected::once(),
                    ]
                ),
                '',
            ],
        );

        $report = $this->make(ErrorReport::class, [
            'toString' => 'my_report',
            'getHash' => 'my_hash',
        ]);

        verify($instance->report('TestMessage', 'TestError', $report));
    }

    public function testMailDebugInfoCached3Skip()
    {
        $instance = $this->construct(
            MailReportFunction::class,
            [
                $this->makeEmpty(
                    NullCache::class,
                    [
                        'get' => Expected::once(['count' => 3]),
                        'set' => Expected::once(),
                    ]
                ),
                $this->makeEmpty(LoggerInterface::class),
                $this->makeEmpty(
                    NullMailService::class,
                    [
                        'send_raw' => Expected::never(),
                    ]
                ),
                '',
            ],
        );

        $report = $this->make(ErrorReport::class, [
            'toString' => 'my_report',
            'getHash' => 'my_hash',
        ]);

        verify($instance->report('TestMessage', 'TestError', $report));
    }

    public function testMailDebugInfoCached24()
    {
        $instance = $this->construct(
            MailReportFunction::class,
            [
                $this->makeEmpty(
                    NullCache::class,
                    [
                        'get' => Expected::once(['count' => 24]),
                        'set' => Expected::once(),
                    ]
                ),
                $this->makeEmpty(LoggerInterface::class),
                $this->makeEmpty(
                    NullMailService::class,
                    [
                        'send_raw' => Expected::once(),
                    ]
                ),
                '',
            ],
        );

        $report = $this->make(ErrorReport::class, [
            'toString' => 'my_report',
            'getHash' => 'my_hash',
        ]);

        verify($instance->report('TestMessage', 'TestError', $report));
    }

    public function testMailDebugInfoCached25Skip()
    {
        $instance = $this->construct(
            MailReportFunction::class,
            [
                $this->makeEmpty(
                    NullCache::class,
                    [
                        'get' => Expected::once(['count' => 25]),
                        'set' => Expected::once(),
                    ]
                ),
                $this->makeEmpty(LoggerInterface::class),
                $this->makeEmpty(
                    NullMailService::class,
                    [
                        'send_raw' => Expected::never(),
                    ]
                ),
                '',
            ],
        );

        $report = $this->make(ErrorReport::class, [
            'toString' => 'my_report',
            'getHash' => 'my_hash',
        ]);

        verify($instance->report('TestMessage', 'TestError', $report));
    }

    private function buildReport(): ErrorReport
    {
        return $this->make(ErrorReport::class, [
            'toString' => 'my_report',
            'getHash' => 'my_hash',
        ]);
    }

    public function testSendFailureIsLoggedAndNotThrown()
    {
        $instance = $this->construct(
            MailReportFunction::class,
            [
                $this->makeEmpty(NullCache::class, ['get' => ['count' => 1]]),
                $this->makeEmpty(LoggerInterface::class, [
                    'error' => Expected::once(function ($message, $context) {
                        verify($message)->equals('Failed to send error report');
                        verify($context['reason'])->equals('mail backend down');
                    }),
                ]),
                $this->makeEmpty(NullMailService::class, [
                    'sendRawMail' => Expected::once(function () {
                        throw new \RuntimeException('mail backend down');
                    }),
                ]),
                '',
            ],
        );

        $instance->report('TestMessage', 'TestError', $this->buildReport());
    }

    public function testRefusedSendIsLogged()
    {
        $instance = $this->construct(
            MailReportFunction::class,
            [
                $this->makeEmpty(NullCache::class, ['get' => ['count' => 1]]),
                $this->makeEmpty(LoggerInterface::class, [
                    'error' => Expected::once(function ($message, $context) {
                        verify($context['reason'])->equals('inactive_address');
                    }),
                ]),
                $this->makeEmpty(NullMailService::class, [
                    'sendRawMail' => Expected::once('inactive_address'),
                ]),
                '',
            ],
        );

        $instance->report('TestMessage', 'TestError', $this->buildReport());
    }
}
