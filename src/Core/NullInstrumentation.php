<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

/**
 * Class NullInstrumentation.
 *
 * A null implementation of the Instrumentation interface that performs no actual instrumentation.
 * Useful for testing or when instrumentation is disabled.
 */
class NullInstrumentation implements Instrumentation
{
    /**
     * Get the current transaction.
     *
     * @return mixed Always returns null
     */
    public function getCurrentTransaction(): mixed
    {
        return null;
    }

    /**
     * Start a new transaction.
     *
     * @param string $op   The operation name for the transaction
     * @param string $name The name of the transaction
     *
     * @return mixed Always returns null
     */
    public function startTransaction(string $op, string $name): mixed
    {
        return null;
    }

    /**
     * Finish a transaction.
     *
     * @param mixed $transaction The transaction object to finish
     */
    public function finishTransaction(mixed $transaction): void
    {
        // No operation
    }

    /**
     * Set the name of a transaction.
     *
     * @param mixed  $transaction The transaction object
     * @param string $name        The new name for the transaction
     */
    public function setTransactionName(mixed $transaction, string $name): void
    {
        // No operation
    }

    /**
     * Start a new span within the current transaction.
     *
     * @param string $op          The operation name for the span
     * @param string $description A description of the span (optional)
     *
     * @return mixed Always returns null
     */
    public function startSpan(string $op, string $description = ''): mixed
    {
        return null;
    }

    /**
     * Finish a span.
     *
     * @param mixed $span The span object to finish
     */
    public function finishSpan(mixed $span): void
    {
        // No operation
    }
}
