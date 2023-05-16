<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use WebFramework\Core\AssertService;
use WebFramework\Core\DebugService;
use WebFramework\Core\MailReportFunction;
use WebFramework\Core\VerifyException;

/**
 * @internal
 *
 * @coversNothing
 */
final class AssertServiceTest extends \Codeception\Test\Unit
{
    public function testVerifyTrue()
    {
        $instance = $this->construct(
            AssertService::class,
            [
                $this->makeEmpty(DebugService::class),
                $this->makeEmpty(MailReportFunction::class),
            ],
            [
                'report_error' => Expected::never(),
            ]
        );

        verify(function () use ($instance) {
            $instance->verify(true, 'TestMessage');
        })
            ->callableDoesNotThrow();
    }

    public function testVerifyFalse()
    {
        $instance = $this->construct(
            AssertService::class,
            [
                $this->makeEmpty(DebugService::class),
                $this->makeEmpty(MailReportFunction::class),
            ],
            [
                'report_error' => Expected::never(),
            ]
        );

        verify(function () use ($instance) {
            $instance->verify(false, 'TestMessage');
        })
            ->callableThrows(VerifyException::class, 'TestMessage');
    }

    public function testVerifyFalseDifferentException()
    {
        $instance = $this->construct(
            AssertService::class,
            [
                $this->makeEmpty(DebugService::class),
                $this->makeEmpty(MailReportFunction::class),
            ],
            [
                'report_error' => Expected::never(),
            ]
        );

        verify(function () use ($instance) {
            $instance->verify(false, 'TestMessage', \InvalidArgumentException::class);
        })
            ->callableThrows(\InvalidArgumentException::class, 'TestMessage');
    }

    public function testReportError()
    {
        $instance = $this->construct(
            AssertService::class,
            [
                $this->makeEmpty(
                    DebugService::class,
                    [
                        'get_error_report' => Expected::once([]),
                    ]
                ),
                $this->makeEmpty(
                    MailReportFunction::class,
                    [
                        'report' => Expected::once(),
                    ]
                ),
            ],
        );

        verify($instance->report_error('TestMessage'));
    }
}
