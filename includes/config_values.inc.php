<?php
/*
CREATE TABLE IF NOT EXISTS config_values (
     module VARCHAR(255) NOT NULL,
     name VARCHAR(255) NOT NULL,
     value VARCHAR(255) NOT NULL,
     UNIQUE KEY `mod_name` (module,name)
);
*/
class ConfigValues
{
    private $database;
    private $default_module;

    function __construct($database, $default_module = "")
    {
        $this->database = $database;
        $this->default_module = $default_module;
    }

    function get_values($module = "")
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->Query('SELECT name, value FROM config_values WHERE module = ?',
            array($module));

        if ($result === FALSE)
            die('Failed to retrieve config values.');

        $info = array();
        
        foreach ($result as $row)
            $info[$row['name']] = $row['value'];

        return $info;
    }

    function get_value($name, $default = "", $module = "")
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->Query('SELECT value FROM config_values WHERE module = ? AND name = ?',
            array($module, $name));

        if ($result === FALSE)
            die('Failed to retrieve config value.');

        if ($result->RecordCount() == 0)
            return $default;

        if ($result->RecordCount() != 1)
            return "";

        return $result->fields['value'];
    }

    function set_value($name, $value, $module = "")
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->Query('INSERT INTO config_values SET module = ?, name = ?, value = ? ON DUPLICATE KEY UPDATE value = ?',
            array($module, $name, $value, $value));

        if ($result === FALSE)
            die('Failed to store config value.');
    }

    function delete_value($name, $module = "")
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->Query('DELETE config_values WHERE module = ? AND name = ?',
            array($module, $name));

        if ($result === FALSE)
            die('Failed to delete config value.');

        return TRUE;
    }
};

/*
CREATE TABLE IF NOT EXISTS user_config_values (
     user_id INT NOT NULL REFERENCES users(id),
     module VARCHAR(255) NOT NULL,
     name VARCHAR(255) NOT NULL,
     value VARCHAR(255) NOT NULL,
     UNIQUE KEY `user_mod_name` (user_id,module,name)
);
*/
class UserConfigValues
{
    private $database;
    private $user_id;
    private $default_module;

    function __construct($database, $user_id, $default_module = "")
    {
        $this->database = $database;
        $this->user_id = $user_id;
        $this->default_module = $default_module;
    }

    function get_values($module = "")
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->Query('SELECT name, value FROM user_config_values WHERE user_id = ? AND module = ?',
            array($this->user_id, $module));

        if ($result === FALSE)
            die('Failed to retrieve config values.');

        $info = array();
        
        foreach ($result as $row)
            $info[$row['name']] = $row['value'];

        return $info;
    }

    function get_value($name, $default = "", $module = "")
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->Query('SELECT value FROM user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            array($this->user_id, $module, $name));

        if ($result === FALSE)
            die('Failed to retrieve config value.');

        if ($result->RecordCount() == 0)
            return $default;

        if ($result->RecordCount() != 1)
            return "";

        return $result->fields['value'];
    }

    function set_value($name, $value, $module = "")
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->Query('INSERT user_config_values SET user_id = ?, module = ?, name = ?, value = ? ON DUPLICATE KEY UPDATE value = ?',
            array($this->user_id, $module, $name, $value, $value));

        if ($result === FALSE)
            die('Failed to store config value.');
    }

    function delete_value($name, $module = "")
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->Query('DELETE user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            array($this->user_id, $module, $name));

        if ($result === FALSE)
            die('Failed to delete config value.');

        return TRUE;
    }
};
?>
