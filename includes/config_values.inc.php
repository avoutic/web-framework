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

    function get_value($name, $module = "")
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->Query('SELECT value FROM config_values WHERE module = ? AND name = ?',
            array($module, $name));

        if ($result === FALSE)
            die('Failed to retrieve config value.');

        if ($result->RecordCount() != 1)
            return "";

        return $result->fields['value'];
    }

    function set_value($name, $value, $module = "")
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->Query('REPLACE INTO config_values SET module = ?, name = ?, value = ?',
            array($module, $name, $value));

        if ($result === FALSE)
            die('Failed to store config value.');
    }
};
?>
