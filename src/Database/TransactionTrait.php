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
     *
     * @return TResult The return value of the callback
     *
     * @throws \Throwable The exception thrown by the callback, after rolling back
     */
    public function transaction(callable $callback): mixed
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

            throw $e;
        }

        $this->commitTransaction();

        return $result;
    }
}
