<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Database\Database;
use WebFramework\Database\NullDatabase;

/**
 * @internal
 *
 * @covers \WebFramework\Database\TransactionTrait
 */
final class DatabaseTransactionTest extends Unit
{
    /** @var array<string> */
    public array $calls = [];

    private function buildDatabase(bool $failRollback = false): Database
    {
        $this->calls = [];
        $test = $this;

        return new class($test, $failRollback) extends NullDatabase {
            public function __construct(private DatabaseTransactionTest $test, private bool $failRollback) {}

            public function startTransaction(): void
            {
                $this->test->calls[] = 'start';
            }

            public function commitTransaction(): void
            {
                $this->test->calls[] = 'commit';
            }

            public function rollbackTransaction(): void
            {
                $this->test->calls[] = 'rollback';

                if ($this->failRollback)
                {
                    throw new \RuntimeException('rollback failed');
                }
            }
        };
    }

    public function testCommitsAndReturnsTheCallbackResult()
    {
        $database = $this->buildDatabase();

        $result = $database->transaction(function (Database $passed) use ($database) {
            verify($passed)->same($database);
            $this->calls[] = 'work';

            return 42;
        });

        verify($result)->same(42);
        verify($this->calls)->same(['start', 'work', 'commit']);
    }

    public function testRollsBackAndRethrowsWhenCallbackThrows()
    {
        $database = $this->buildDatabase();
        $exception = new \LogicException('work failed');
        $caught = null;

        try
        {
            $database->transaction(function () use ($exception) {
                throw $exception;
            });
        }
        catch (\Throwable $e)
        {
            $caught = $e;
        }

        verify($caught)->same($exception);
        verify($this->calls)->same(['start', 'rollback']);
    }

    public function testRethrowsOriginalExceptionWhenRollbackFails()
    {
        $database = $this->buildDatabase(true);
        $exception = new \LogicException('work failed');
        $caught = null;

        try
        {
            $database->transaction(function () use ($exception) {
                throw $exception;
            });
        }
        catch (\Throwable $e)
        {
            $caught = $e;
        }

        verify($caught)->same($exception);
    }

    public function testNullDatabaseRunsTheCallback()
    {
        verify((new NullDatabase())->transaction(fn () => 'done'))->same('done');
    }
}
