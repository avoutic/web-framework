<?php
namespace WebFramework\Core;

use ADOConnection;

class Database {
	private ADOConnection $database;

    /**
     * @param array<string> $config
     */
    public function connect(array $config): bool
    {
        $database = ADONewConnection($config['database_type']);
        if (!$database)
            return false;

        $this->database = $database;

        $result = $this->database->PConnect(
            $config['database_host'],
            $config['database_user'],
            $config['database_password'],
            $config['database_database']
        );

        if (!$result)
            return false;

        return true;
	}

    /**
     * @param array<bool|int|string> $value_array
     */
	public function query(string $query_str, array $value_array): mixed
	{
		if (!$this->database->IsConnected())
			die('Database connection not available. Exiting.');

		$query = $this->database->Prepare($query_str);

		return $this->database->Execute($query, $value_array);
	}

    /**
     * @param array<bool|int|string> $params
     */
    public function insert_query(string $query, array $params): mixed
	{
		$result = $this->query($query, $params);

		if ($result !== false)
			return $this->database->Insert_ID();

		return false;
	}

    public function get_last_error(): string
    {
        return $this->database->errorMsg();
    }
}
?>
