<?php
require('adodb/adodb.inc.php');

class Database {
	private $database;

	function Connect($config) {
        $this->database = ADONewConnection($config['database_type']);
        if (!$this->database)
            return FALSE;

        $result = $this->database->PConnect($config['database_host'],
                $config['database_user'],
                $config['database_password'],
                $config['database_database']
                );

        if (!$result)
            return FALSE;

        return TRUE;
	}

	function Query($query_str, $value_array)
	{
		if (!$this->database || !$this->database->IsConnected())
		{
			die('Database connection not available. Exiting.');
		}

		$query = $this->database->Prepare($query_str);
		return $this->database->Execute($query, $value_array);
	}

	function InsertQuery($query_str, $value_array)
	{
		$result = $this->Query($query_str, $value_array);

		if ($result !== FALSE)
			return $this->database->Insert_ID();

		return FALSE;
	}	
}
?>
