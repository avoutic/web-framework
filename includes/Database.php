<?php

namespace WebFramework\Core;

class Database
{
    private \mysqli $database;

    /**
     * @param array<string> $config
     */
    public function connect(array $config): bool
    {
        $database = new \mysqli(
            $config['database_host'],
            $config['database_user'],
            $config['database_password'],
            $config['database_database']
        );

        if ($database->connect_error)
        {
            return false;
        }

        $this->database = $database;

        return true;
    }

    /**
     * @param array<null|bool|int|string> $value_array
     */
    public function query(string $query_str, array $value_array): DatabaseResultWrapper|false
    {
        if (!$this->database->ping())
        {
            exit('Database connection not available. Exiting.');
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
     * @param array<bool|int|string> $params
     */
    public function insert_query(string $query, array $params): int|false
    {
        $result = $this->query($query, $params);

        if ($result !== false)
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
            exit('Query failed to check for table existence. Exiting.');
        }

        return $result->RecordCount() == 1;
    }
}
