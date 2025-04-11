<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WebFramework\Core\MailReportFunction;
use WebFramework\Core\NullCache;
use WebFramework\Core\NullMailService;

/**
 * @internal
 *
 * @coversNothing
 */
final class MailReportFunctionTest extends Unit
{
    public function testReportUncached()
    {
        $instance = $this->construct(
            MailReportFunction::class,
            [
                $this->makeEmpty(
                    NullCache::class,
                    [
                        'get' => Expected::once(false),
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

        verify($instance->report('TestMessage', 'TestError', ['title' => 'TestTitle', 'message' => 'TestDebug', 'low_info_message' => 'TestLowInfo', 'hash' => 'my_hash']));
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

        verify($instance->report('TestMessage', 'TestError', ['title' => 'TestTitle', 'message' => 'TestDebug', 'low_info_message' => 'TestLowInfo', 'hash' => 'my_hash']));
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

        verify($instance->report('TestMessage', 'TestError', ['title' => 'TestTitle', 'message' => 'TestDebug', 'low_info_message' => 'TestLowInfo', 'hash' => 'my_hash']));
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

        verify($instance->report('TestMessage', 'TestError', ['title' => 'TestTitle', 'message' => 'TestDebug', 'low_info_message' => 'TestLowInfo', 'hash' => 'my_hash']));
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

        verify($instance->report('TestMessage', 'TestError', ['title' => 'TestTitle', 'message' => 'TestDebug', 'low_info_message' => 'TestLowInfo', 'hash' => 'my_hash']));
    }
}
