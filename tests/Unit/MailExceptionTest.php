<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Exception\MailException;

/**
 * @internal
 *
 * @covers \WebFramework\Exception\MailException
 */
final class MailExceptionTest extends Unit
{
    public function testCarriesBackendErrorCodeAndPreviousException()
    {
        $previous = new \Exception('backend failure');

        $exception = new MailException('Failed to send mail', 10, $previous);

        verify($exception)->instanceOf(\RuntimeException::class);
        verify($exception->getMessage())->equals('Failed to send mail');
        verify($exception->getErrorCode())->same(10);
        verify($exception->getPrevious())->same($previous);
    }

    public function testErrorCodeIsOptional()
    {
        $exception = new MailException('Mail backend connection failure');

        verify($exception->getErrorCode())->null();
        verify($exception->getCode())->same(0);
    }
}
