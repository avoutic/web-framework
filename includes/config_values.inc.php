<?php
/*
CREATE TABLE IF NOT EXISTS config_values (
     module VARCHAR(255) NOT NULL,
     name VARCHAR(255) NOT NULL,
     value VARCHAR(255) NOT NULL,
     UNIQUE KEY `mod_name` (module,name)
);
*/

// Not a FrameworkCore object because it needs to operate before all components have been loaded
//
class ConfigValues
{
    private Database $database;
    private string $default_module;

    function __construct(string $default_module = "")
    {
        $this->database = WF::get_main_db();
        $this->default_module = $default_module;
    }

    /**
     * @return array<string>
     */
    public function get_values(string $module = ""): array
    {
        if ($module == "")
            $module = $this->default_module;

        $result = $this->database->query('SELECT name, value FROM config_values WHERE module = ?',
            array($module));

        if ($result === false)
            die('Failed to retrieve config values.');

        $info = array();

        foreach ($result as $row)
            $info[$row['name']] = $row['value'];

        return $info;
    }

    public function get_value(string $name, string $default = "", string $module = ""): string
    {
        if ($module == "")
            $module = $this->default_module;

        $result = $this->database->query('SELECT value FROM config_values WHERE module = ? AND name = ?',
            array($module, $name));

        if ($result === false)
            die('Failed to retrieve config value.');

        if ($result->RecordCount() == 0)
            return $default;

        if ($result->RecordCount() != 1)
            return "";

        return $result->fields['value'];
    }

    public function set_value(string $name, string $value, string $module = ""): void
    {
        if ($module == "")
            $module = $this->default_module;

        $result = $this->database->query('INSERT INTO config_values SET module = ?, name = ?, value = ? ON DUPLICATE KEY UPDATE value = ?',
            array($module, $name, $value, $value));

        if ($result === false)
            die('Failed to store config value.');
    }

    public function delete_value(string $name, string $module = ""): void
    {
        if ($module == "")
            $module = $this->default_module;

        $result = $this->database->query('DELETE config_values WHERE module = ? AND name = ?',
            array($module, $name));

        if ($result === false)
            die('Failed to delete config value.');
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
    private Database $database;
    private int $user_id;
    private string $default_module;

    function __construct(int $user_id, string $default_module = "")
    {
        $this->database = WF::get_main_db();
        $this->user_id = $user_id;
        $this->default_module = $default_module;
    }

    /**
     * @return array<string>
     */
    public function get_values(string $module = ""): array
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->query('SELECT name, value FROM user_config_values WHERE user_id = ? AND module = ?',
            array($this->user_id, $module));

        if ($result === false)
            die('Failed to retrieve config values.');

        $info = array();

        foreach ($result as $row)
            $info[$row['name']] = $row['value'];

        return $info;
    }

    public function value_exists(string $name, string $module = ""): bool
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->query('SELECT value FROM user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            array($this->user_id, $module, $name));

        if ($result === false)
            die('Failed to retrieve config value.');

        if ($result->RecordCount() == 0)
            return false;

        return true;
    }

    public function get_value(string $name, string $default = "", string $module = ""): string
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->query('SELECT value FROM user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            array($this->user_id, $module, $name));

        if ($result === false)
            die('Failed to retrieve config value.');

        if ($result->RecordCount() == 0)
            return $default;

        if ($result->RecordCount() != 1)
            return "";

        return $result->fields['value'];
    }

    public function set_value(string $name, string $value, string $module = ""): void
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->query('INSERT user_config_values SET user_id = ?, module = ?, name = ?, value = ? ON DUPLICATE KEY UPDATE value = ?',
            array($this->user_id, $module, $name, $value, $value));

        if ($result === false)
            die('Failed to store config value.');
    }

    public function delete_value(string $name, string $module = ""): void
    {
        if ($module == "")
            $module == $this->default_module;

        $result = $this->database->query('DELETE user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            array($this->user_id, $module, $name));

        if ($result === false)
            die('Failed to delete config value.');
    }
};
?>
