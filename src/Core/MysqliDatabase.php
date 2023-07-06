<?php

namespace WebFramework\Core;

class MysqliDatabase implements Database
{
    private int $transactionDepth = 0;

    public function __construct(
        private \mysqli $database,
    ) {
    }

    /**
     * @param array<null|bool|float|int|string> $valueArray
     */
    public function query(string $queryStr, array $valueArray): DatabaseResultWrapper|false
    {
        if (!$this->database->ping())
        {
            throw new \RuntimeException('Database connection not available');
        }

        $result = null;

        if (!count($valueArray))
        {
            $result = $this->database->query($queryStr);
        }
        else
        {
            $result = $this->database->execute_query($queryStr, $valueArray);
        }

        if (!$result)
        {
            return false;
        }

        return new DatabaseResultWrapper($result);
    }

    /**
     * @param array<null|bool|float|int|string> $params
     */
    public function insertQuery(string $query, array $params): int|false
    {
        $result = $this->query($query, $params);

        if ($this->database !== null && $result !== false)
        {
            return (int) $this->database->insert_id;
        }

        return false;
    }

    public function getLastError(): string
    {
        return $this->database->error;
    }

    public function tableExists(string $tableName): bool
    {
        $query = "SHOW TABLES LIKE '{$tableName}'";

        $result = $this->query($query, []);
        if ($result === false)
        {
            throw new \RuntimeException('Check for table existence failed');
        }

        return $result->RecordCount() == 1;
    }

    public function startTransaction(): void
    {
        // MariaDB does not support recursive transactions, so simulate by counting depth
        //
        if ($this->transactionDepth == 0)
        {
            $result = $this->query('START TRANSACTION', []);
            if ($result === false)
            {
                throw new \RuntimeException('Failed to start transaction');
            }
        }

        $this->transactionDepth++;
    }

    public function commitTransaction(): void
    {
        // MariaDB does not support recursive transactions, so only commit the final transaction
        //
        if ($this->transactionDepth == 1)
        {
            $result = $this->query('COMMIT', []);
            if ($result === false)
            {
                throw new \RuntimeException('Failed to commit transaction');
            }
        }

        $this->transactionDepth--;
    }

    public function getTransactionDepth(): int
    {
        return $this->transactionDepth;
    }
}
