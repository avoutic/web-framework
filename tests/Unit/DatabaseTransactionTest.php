<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Database\Database;
use WebFramework\Database\NullDatabase;
use WebFramework\Exception\DatabaseErrorType;
use WebFramework\Exception\DatabaseException;

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

    public int $depth = 0;

    /** @var array<int> */
    public array $waits = [];

    private function buildRetryDatabase(): Database
    {
        $this->calls = [];
        $this->depth = 0;
        $this->waits = [];

        return new class($this) extends NullDatabase {
            public function __construct(private DatabaseTransactionTest $test) {}

            public function startTransaction(): void
            {
                $this->test->depth++;
                $this->test->calls[] = 'start';
            }

            public function commitTransaction(): void
            {
                $this->test->depth--;
                $this->test->calls[] = 'commit';
            }

            public function rollbackTransaction(): void
            {
                $this->test->depth--;
                $this->test->calls[] = 'rollback';
            }

            public function getTransactionDepth(): int
            {
                return $this->test->depth;
            }

            protected function waitBeforeRetry(int $attempt): void
            {
                $this->test->waits[] = $attempt;
            }
        };
    }

    private function deadlock(): DatabaseException
    {
        return new DatabaseException('Deadlock found', 1213, '40001', DatabaseErrorType::Deadlock);
    }

    public function testRetriesTheCallbackAfterARetryableError()
    {
        $database = $this->buildRetryDatabase();
        $runs = 0;

        $result = $database->transaction(function () use (&$runs) {
            $runs++;

            if ($runs < 3)
            {
                throw $this->deadlock();
            }

            return 'done';
        }, 3);

        verify($result)->same('done');
        verify($runs)->equals(3);
        verify($this->calls)->same(['start', 'rollback', 'start', 'rollback', 'start', 'commit']);
        verify($this->waits)->same([1, 2]);
    }

    public function testGivesUpWhenTheAttemptsRunOut()
    {
        $database = $this->buildRetryDatabase();
        $runs = 0;
        $caught = null;

        try
        {
            $database->transaction(function () use (&$runs) {
                $runs++;

                throw $this->deadlock();
            }, 2);
        }
        catch (DatabaseException $e)
        {
            $caught = $e;
        }

        verify($caught)->notNull();
        verify($runs)->equals(2);
        verify($this->depth)->equals(0);
    }

    public function testDoesNotRetryByDefault()
    {
        $database = $this->buildRetryDatabase();
        $runs = 0;

        verify(function () use ($database, &$runs) {
            $database->transaction(function () use (&$runs) {
                $runs++;

                throw $this->deadlock();
            });
        })->callableThrows(DatabaseException::class);

        verify($runs)->equals(1);
    }

    public function testDoesNotRetryOtherErrors()
    {
        $database = $this->buildRetryDatabase();
        $runs = 0;

        verify(function () use ($database, &$runs) {
            $database->transaction(function () use (&$runs) {
                $runs++;

                throw new DatabaseException('Duplicate entry', 1062, '23000', DatabaseErrorType::DuplicateKey);
            }, 3);
        })->callableThrows(DatabaseException::class);

        verify($runs)->equals(1);

        $runs = 0;

        verify(function () use ($database, &$runs) {
            $database->transaction(function () use (&$runs) {
                $runs++;

                throw new \LogicException('not a database error');
            }, 3);
        })->callableThrows(\LogicException::class);

        verify($runs)->equals(1);
    }

    public function testNestedTransactionLeavesTheRetryToTheOutermostOne()
    {
        $database = $this->buildRetryDatabase();
        $outerRuns = 0;
        $innerRuns = 0;

        $result = $database->transaction(function () use ($database, &$outerRuns, &$innerRuns) {
            $outerRuns++;

            return $database->transaction(function () use (&$innerRuns) {
                $innerRuns++;

                if ($innerRuns === 1)
                {
                    throw $this->deadlock();
                }

                return 'done';
            }, 5);
        }, 2);

        // The inner one did not retry by itself: the whole transaction ran again
        verify($result)->same('done');
        verify($outerRuns)->equals(2);
        verify($innerRuns)->equals(2);
        verify($this->depth)->equals(0);
    }
}
