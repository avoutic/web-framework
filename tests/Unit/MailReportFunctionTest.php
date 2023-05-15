<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use WebFramework\Core\MailReportFunction;
use WebFramework\Core\NullCache;
use WebFramework\Core\NullMailService;

/**
 * @internal
 *
 * @coversNothing
 */
final class MailReportFunctionTest extends \Codeception\Test\Unit
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
                        'get' => Expected::once(['count' => 2, 'last_timestamp' => 1]),
                        'set' => Expected::once(),
                    ]
                ),
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
                        'get' => Expected::once(['count' => 3, 'last_timestamp' => 1]),
                        'set' => Expected::once(),
                    ]
                ),
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
                        'get' => Expected::once(['count' => 24, 'last_timestamp' => 1]),
                        'set' => Expected::once(),
                    ]
                ),
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
                        'get' => Expected::once(['count' => 25, 'last_timestamp' => 1]),
                        'set' => Expected::once(),
                    ]
                ),
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
