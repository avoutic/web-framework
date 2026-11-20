<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Exception\DatabaseErrorType;
use WebFramework\Exception\DatabaseException;

/**
 * @internal
 *
 * @covers \WebFramework\Exception\DatabaseException
 */
final class DatabaseExceptionTest extends Unit
{
    public function testCarriesDriverDetailsAndPreviousException()
    {
        $previous = new \Exception('driver failure');

        $exception = new DatabaseException(
            'Failed to create object',
            1062,
            '23000',
            DatabaseErrorType::DuplicateKey,
            "Duplicate entry 'a' for key 'email'",
            $previous,
        );

        verify($exception)->instanceOf(\RuntimeException::class);
        verify($exception->getMessage())->equals('Failed to create object');
        verify($exception->getErrorCode())->same(1062);
        verify($exception->getCode())->same(1062);
        verify($exception->getSqlState())->same('23000');
        verify($exception->getDriverMessage())->equals("Duplicate entry 'a' for key 'email'");
        verify($exception->getPrevious())->same($previous);
        verify($exception->getType())->same(DatabaseErrorType::DuplicateKey);
    }

    public function testHelpersFollowTheErrorType()
    {
        $duplicate = new DatabaseException('m', 1062, '23000', DatabaseErrorType::DuplicateKey);
        $timeout = new DatabaseException('m', 1205, 'HY000', DatabaseErrorType::LockTimeout);
        $deadlock = new DatabaseException('m', 1213, '40001', DatabaseErrorType::Deadlock);

        verify($duplicate->isDuplicateKey())->true();
        verify($duplicate->isLockTimeout())->false();
        verify($duplicate->isDeadlock())->false();

        verify($timeout->isLockTimeout())->true();
        verify($timeout->isDuplicateKey())->false();

        verify($deadlock->isDeadlock())->true();
        verify($deadlock->isLockTimeout())->false();
    }

    public function testDefaultsWhenDriverGivesNoDetails()
    {
        $exception = new DatabaseException('Database connection not available');

        verify($exception->getErrorCode())->null();
        verify($exception->getCode())->same(0);
        verify($exception->getSqlState())->null();
        verify($exception->getDriverMessage())->equals('');
        verify($exception->getType())->same(DatabaseErrorType::Other);
        verify($exception->isDuplicateKey())->false();
    }
}
