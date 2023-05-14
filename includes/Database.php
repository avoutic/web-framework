<?php

namespace WebFramework\Core;

class Database
{
    private int $transaction_depth = 0;

    public function __construct(
        private \mysqli $database,
        protected AssertService $assert_service,
    ) {
    }

    /**
     * @param array<null|bool|float|int|string> $value_array
     */
    public function query(string $query_str, array $value_array): DatabaseResultWrapper|false
    {
        if (!$this->database->ping())
        {
            throw new \RuntimeException('Database connection not available');
        }

        $result = null;

        if (!count($value_array))
        {
            $result = $this->database->query($query_str);
        }
        else
        {
            $result = $this->database->execute_query($query_str, $value_array);
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
    public function insert_query(string $query, array $params): int|false
    {
        $result = $this->query($query, $params);

        if ($this->database !== null && $result !== false)
        {
            return (int) $this->database->insert_id;
        }

        return false;
    }

    public function get_last_error(): string
    {
        return $this->database->error;
    }

    public function table_exists(string $table_name): bool
    {
        $query = "SHOW TABLES LIKE '{$table_name}'";

        $result = $this->query($query, []);
        if ($result === false)
        {
            throw new \RuntimeException('Check for table existence failed');
        }

        return $result->RecordCount() == 1;
    }

    public function start_transaction(): void
    {
        // MariaDB does not support recursive transactions, so simulate by counting depth
        //
        if ($this->transaction_depth == 0)
        {
            $result = $this->query('START TRANSACTION', []);
            $this->assert_service->verify($result !== false, 'Failed to start transaction');
        }

        $this->transaction_depth++;
    }

    public function commit_transaction(): void
    {
        // MariaDB does not support recursive transactions, so only commit the final transaction
        //
        if ($this->transaction_depth == 1)
        {
            $result = $this->query('COMMIT', []);
            $this->assert_service->verify($result !== false, 'Failed to commit transaction');
        }

        $this->transaction_depth--;
    }

    public function get_transaction_depth(): int
    {
        return $this->transaction_depth;
    }
}
