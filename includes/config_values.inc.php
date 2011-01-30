<?php
class ConfigValues
{
    private $database;
    private $default_module;

    function __construct($database, $default_module = "")
    {
        $this->database = $database;
        $this->default_module = $default_module;
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
};

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
};
?>
