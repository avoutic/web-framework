<?php

namespace WebFramework\Core;

/**
 * Class MysqliDatabase.
 *
 * Implements the Database interface using MySQLi.
 */
class MysqliDatabase implements Database
{
    /** @var int The current transaction nesting depth */
    private int $transactionDepth = 0;

    /**
     * MysqliDatabase constructor.
     *
     * @param \mysqli         $database        The MySQLi connection object
     * @param Instrumentation $instrumentation The instrumentation service for performance tracking
     */
    public function __construct(
        private \mysqli $database,
        private Instrumentation $instrumentation,
    ) {}

    /**
     * Execute a database query.
     *
     * @param string                            $queryStr         The SQL query string
     * @param array<null|bool|float|int|string> $valueArray       An array of values to be bound to the query
     * @param string                            $exceptionMessage The message to use if an exception is thrown
     *
     * @return DatabaseResultWrapper The result of the query
     *
     * @throws \RuntimeException If the database connection is not available or if the query fails
     */
    public function query(string $queryStr, array $valueArray, string $exceptionMessage = 'Query failed'): DatabaseResultWrapper
    {
        if (!$this->database->ping())
        {
            throw new \RuntimeException('Database connection not available');
        }

        $span = $this->instrumentation->startSpan('db.sql.query', $queryStr);

        $result = null;

        if (!count($valueArray))
        {
            $result = $this->database->query($queryStr);
        }
        else
        {
            $result = $this->database->execute_query($queryStr, $valueArray);
        }

        $this->instrumentation->finishSpan($span);

        if (!$result)
        {
            throw new \RuntimeException($exceptionMessage);
        }

        return new DatabaseResultWrapper($result);
    }

    /**
     * Execute an INSERT query and return the last inserted ID.
     *
     * @param string                            $query            The SQL INSERT query
     * @param array<null|bool|float|int|string> $params           An array of parameters to be bound to the query
     * @param string                            $exceptionMessage The message to use if an exception is thrown
     *
     * @return int The ID of the last inserted row
     *
     * @throws \RuntimeException If the query fails
     */
    public function insertQuery(string $query, array $params, string $exceptionMessage = 'Query failed'): int
    {
        $this->query($query, $params, $exceptionMessage);

        return (int) $this->database->insert_id;
    }

    /**
     * Get the last error message from the database.
     *
     * @return string The last error message
     */
    public function getLastError(): string
    {
        return $this->database->error;
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
        $query = "SHOW TABLES LIKE '{$tableName}'";

        $result = $this->query($query, [], "Failed to check for table {$tableName}");

        return $result->RecordCount() == 1;
    }

    /**
     * Start a database transaction.
     */
    public function startTransaction(): void
    {
        // MariaDB does not support recursive transactions, so simulate by counting depth
        if ($this->transactionDepth == 0)
        {
            $this->query('START TRANSACTION', [], 'Failed to start transaction');
        }

        $this->transactionDepth++;
    }

    /**
     * Commit the current database transaction.
     */
    public function commitTransaction(): void
    {
        // MariaDB does not support recursive transactions, so only commit the final transaction
        if ($this->transactionDepth == 1)
        {
            $this->query('COMMIT', [], 'Failed to commit transaction');
        }

        $this->transactionDepth--;
    }

    /**
     * Get the current transaction nesting depth.
     *
     * @return int The current transaction depth
     */
    public function getTransactionDepth(): int
    {
        return $this->transactionDepth;
    }
}
