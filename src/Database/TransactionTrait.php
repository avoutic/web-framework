<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Database;

use WebFramework\Exception\DatabaseException;

/**
 * Implements Database::transaction() on top of startTransaction(), commitTransaction()
 * and rollbackTransaction().
 */
trait TransactionTrait
{
    /**
     * Run a callback inside a database transaction.
     *
     * @template TResult
     *
     * @param callable(Database): TResult $callback The work to do inside the transaction
     * @param int                         $attempts How often to try when the transaction fails with a
     *                                              retryable error, such as a deadlock
     *
     * @return TResult The return value of the callback
     *
     * @throws \Throwable The exception thrown by the callback, after rolling back
     */
    public function transaction(callable $callback, int $attempts = 1): mixed
    {
        // A retryable error, such as a deadlock, ends the whole transaction in the database,
        // not only the nested part. So only the outermost transaction can try again
        //
        if ($this->getTransactionDepth() > 0)
        {
            $attempts = 1;
        }

        for ($attempt = 1;; $attempt++)
        {
            $this->startTransaction();

            try
            {
                $result = $callback($this);
            }
            catch (\Throwable $e)
            {
                try
                {
                    $this->rollbackTransaction();
                }
                catch (\Throwable $rollbackError)
                {
                    // The exception of the callback is the one that matters to the caller
                }

                if ($attempt < $attempts && $e instanceof DatabaseException && $e->isRetryable())
                {
                    $this->waitBeforeRetry($attempt);

                    continue;
                }

                throw $e;
            }

            $this->commitTransaction();

            return $result;
        }
    }

    /**
     * Wait before a transaction is tried again, so the transaction that got in the way
     * can finish. A bit longer after every attempt, and never exactly the same, so two
     * transactions that keep colliding get out of step.
     *
     * @param int $attempt The number of the attempt that failed
     */
    protected function waitBeforeRetry(int $attempt): void
    {
        usleep(random_int(5, 50) * 1000 * $attempt);
    }
}
