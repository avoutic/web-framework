<?php

namespace WebFramework\Core;

class MysqliDatabase implements Database
{
    private int $transactionDepth = 0;

    public function __construct(
        private \mysqli $database,
        private Instrumentation $instrumentation,
    ) {
    }

    /**
     * @param array<null|bool|float|int|string> $valueArray
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
     * @param array<null|bool|float|int|string> $params
     */
    public function insertQuery(string $query, array $params, string $exceptionMessage = 'Query failed'): int
    {
        $this->query($query, $params, $exceptionMessage);

        return (int) $this->database->insert_id;
    }

    public function getLastError(): string
    {
        return $this->database->error;
    }

    public function tableExists(string $tableName): bool
    {
        $query = "SHOW TABLES LIKE '{$tableName}'";

        $result = $this->query($query, [], "Failed to check for table {$tableName}");

        return $result->RecordCount() == 1;
    }

    public function startTransaction(): void
    {
        // MariaDB does not support recursive transactions, so simulate by counting depth
        //
        if ($this->transactionDepth == 0)
        {
            $this->query('START TRANSACTION', [], 'Failed to start transaction');
        }

        $this->transactionDepth++;
    }

    public function commitTransaction(): void
    {
        // MariaDB does not support recursive transactions, so only commit the final transaction
        //
        if ($this->transactionDepth == 1)
        {
            $this->query('COMMIT', [], 'Failed to commit transaction');
        }

        $this->transactionDepth--;
    }

    public function getTransactionDepth(): int
    {
        return $this->transactionDepth;
    }
}
