<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WebFramework\Diagnostics\NullReportFunction;
use WebFramework\Diagnostics\ReportFunction;
use WebFramework\Logging\ChannelManager;
use WebFramework\SanityCheck\ErrorReporting;

/**
 * @internal
 *
 * @covers \WebFramework\SanityCheck\ErrorReporting
 */
final class ErrorReportingSanityCheckTest extends Unit
{
    private function buildCheck(LoggerInterface $exceptionLogger, ReportFunction $reportFunction, mixed $emergency): ErrorReporting
    {
        return new ErrorReporting(
            $this->makeEmpty(ChannelManager::class, ['getExceptionLogger' => $exceptionLogger]),
            $this->makeEmpty(LoggerInterface::class, ['emergency' => $emergency]),
            $reportFunction,
        );
    }

    public function testFailsWhenErrorsGoNowhere()
    {
        $check = $this->buildCheck(new NullLogger(), new NullReportFunction(), Expected::once());

        verify($check->performChecks())->false();
    }

    public function testPassesWhenExceptionsAreLogged()
    {
        $check = $this->buildCheck($this->makeEmpty(LoggerInterface::class), new NullReportFunction(), Expected::never());

        verify($check->performChecks())->true();
    }

    public function testPassesWhenErrorsAreReported()
    {
        $check = $this->buildCheck(new NullLogger(), $this->makeEmpty(ReportFunction::class), Expected::never());

        verify($check->performChecks())->true();
    }
}
