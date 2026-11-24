<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Database\Database;
use WebFramework\Database\DatabaseResultWrapper;
use WebFramework\Database\Lock;

/**
 * @internal
 *
 * @covers \WebFramework\Database\Lock
 */
final class LockTest extends Unit
{
    /** @var array<string> */
    private array $queries = [];

    /**
     * @param array<mixed> $answers The answers of the database, in order. An exception is thrown
     */
    private function buildLock(array $answers): Lock
    {
        $this->queries = [];

        $database = $this->makeEmpty(Database::class, [
            'query' => function (string $query) use (&$answers) {
                $this->queries[] = str_contains($query, 'GET_LOCK') ? 'lock' : 'release';

                $answer = array_shift($answers);

                if ($answer instanceof \Throwable)
                {
                    throw $answer;
                }

                $result = $this->makeEmpty(DatabaseResultWrapper::class);
                $result->fields = ['result' => $answer];

                return $result;
            },
        ]);

        return new Lock($database, 'test-lock');
    }

    public function testLockAndRelease()
    {
        $lock = $this->buildLock([1, 1]);

        verify($lock->lock())->true();
        verify($lock->release())->true();

        unset($lock);

        // Released once, not again when the lock is destroyed
        verify($this->queries)->same(['lock', 'release']);
    }

    public function testLockThatIsNotAcquiredIsNotReleased()
    {
        $lock = $this->buildLock([0]);

        verify($lock->lock())->false();

        unset($lock);

        verify($this->queries)->same(['lock']);
    }

    public function testReleaseThatTheDatabaseRefusesIsNotTriedAgain()
    {
        // The connection no longer holds the lock, for example after a reconnect
        foreach ([0, null] as $answer)
        {
            $lock = $this->buildLock([1, $answer]);

            $lock->lock();

            verify($lock->release())->false();

            unset($lock);

            verify($this->queries)->same(['lock', 'release']);
        }
    }

    public function testReleaseThatFailsIsNotTriedAgain()
    {
        $lock = $this->buildLock([1, new \RuntimeException('connection lost')]);

        $lock->lock();

        verify(function () use ($lock) {
            $lock->release();
        })->callableThrows(\RuntimeException::class, 'connection lost');

        unset($lock);

        verify($this->queries)->same(['lock', 'release']);
    }

    public function testDestructorReleasesAndNeverThrows()
    {
        $lock = $this->buildLock([1, new \RuntimeException('connection lost')]);

        $lock->lock();

        unset($lock);

        verify($this->queries)->same(['lock', 'release']);
    }

    public function testBlockReleasesAfterTheCallback()
    {
        $lock = $this->buildLock([1, 0]);

        verify($lock->block(fn () => 'done'))->equals('done');

        unset($lock);

        verify($this->queries)->same(['lock', 'release']);
    }
}
