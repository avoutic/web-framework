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
 * Class NullDatabase.
 *
 * A null implementation of the Database interface that returns no data.
 */
class NullDatabase implements Database
{
    /**
     * Execute a database query.
     *
     * @param string                            $queryStr         The SQL query string
     * @param array<null|bool|float|int|string> $valueArray       An array of values to be bound to the query
     * @param string                            $exceptionMessage The message to use if an exception is thrown
     *
     * @return DatabaseResultWrapper The result of the query
     *
     * @throws \RuntimeException If the query fails
     */
    public function query(string $queryStr, array $valueArray, string $exceptionMessage = ''): DatabaseResultWrapper
    {
        return new DatabaseResultWrapper(true);
    }

    /**
     * Execute an INSERT query and return the last inserted ID.
     *
     * @param string                            $query            The SQL INSERT query
     * @param array<null|bool|float|int|string> $params           An array of parameters to be bound to the query
     * @param string                            $exceptionMessage The message to use if an exception is thrown
     *
     * @return int The ID of the last inserted row
     */
    public function insertQuery(string $query, array $params, string $exceptionMessage = ''): int
    {
        return -1;
    }

    /**
     * Get the last error message from the database.
     *
     * @return string The last error message
     */
    public function getLastError(): string
    {
        return '';
    }

    /**
     * Check if a table exists in the database.
     *
     * @param string $tableName The name of the table to check
     *
     * @return bool True if the table exists, false otherwise
     */
    public function tableExists(string $tableName): bool
    {
        return false;
    }

    /**
     * Start a database transaction.
     */
    public function startTransaction(): void
    {
        // No operation
    }

    /**
     * Commit the current database transaction.
     */
    public function commitTransaction(): void
    {
        // No operation
    }

    /**
     * Rollback the current database transaction.
     */
    public function rollbackTransaction(): void
    {
        // No operation
    }

    /**
     * Get the current transaction nesting depth.
     *
     * @return int The current transaction depth
     */
    public function getTransactionDepth(): int
    {
        return 0;
    }
}
