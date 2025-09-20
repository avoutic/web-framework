<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Diagnostics;

/**
 * Interface Instrumentation.
 *
 * Defines the contract for instrumentation implementations in the WebFramework.
 * This interface is used for performance monitoring and tracing.
 */
interface Instrumentation
{
    /**
     * Get the current transaction.
     *
     * @return mixed The current transaction object or null if no transaction is active
     */
    public function getCurrentTransaction(): mixed;

    /**
     * Start a new transaction.
     *
     * @param string $op   The operation name for the transaction
     * @param string $name The name of the transaction
     *
     * @return mixed The newly created transaction object
     */
    public function startTransaction(string $op, string $name): mixed;

    /**
     * Finish a transaction.
     *
     * @param mixed $transaction The transaction object to finish
     */
    public function finishTransaction(mixed $transaction): void;

    /**
     * Set the name of a transaction.
     *
     * @param mixed  $transaction The transaction object
     * @param string $name        The new name for the transaction
     */
    public function setTransactionName(mixed $transaction, string $name): void;

    /**
     * Start a new span within the current transaction.
     *
     * @param string $op          The operation name for the span
     * @param string $description A description of the span (optional)
     *
     * @return mixed The newly created span object
     */
    public function startSpan(string $op, string $description = ''): mixed;

    /**
     * Finish a span.
     *
     * @param mixed $span The span object to finish
     */
    public function finishSpan(mixed $span): void;
}
