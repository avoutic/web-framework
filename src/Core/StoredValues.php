<?php

namespace WebFramework\Core;

/*
CREATE TABLE IF NOT EXISTS config_values (
     module VARCHAR(255) NOT NULL,
     name VARCHAR(255) NOT NULL,
     value VARCHAR(255) NOT NULL,
     UNIQUE KEY `mod_name` (module,name)
);
*/

/**
 * Class StoredValues.
 *
 * Manages stored configuration values in the database.
 */
class StoredValues
{
    /**
     * StoredValues constructor.
     *
     * @param Database $database The database interface
     * @param string   $module   The module name for which values are stored
     */
    public function __construct(
        private Database $database,
        private string $module,
    ) {}

    /**
     * Get all stored values for the current module.
     *
     * @return array<string> An associative array of stored values
     */
    public function getValues(): array
    {
        $result = $this->database->query(
            'SELECT name, value FROM config_values WHERE module = ?',
            [$this->module],
            "Failed to retrieve stored values for {$this->module}",
        );

        $info = [];

        foreach ($result as $row)
        {
            $info[$row['name']] = $row['value'];
        }

        return $info;
    }

    /**
     * Get a specific stored value.
     *
     * @param string $name    The name of the value to retrieve
     * @param string $default The default value to return if not found
     *
     * @return string The stored value or the default value
     */
    public function getValue(string $name, string $default = ''): string
    {
        $result = $this->database->query(
            'SELECT value FROM config_values WHERE module = ? AND name = ?',
            [$this->module, $name],
            "Failed to retrieve stored value for {$this->module}:{$name}",
        );

        if ($result->RecordCount() == 0)
        {
            return $default;
        }

        if ($result->RecordCount() != 1)
        {
            return '';
        }

        return $result->fields['value'];
    }

    /**
     * Set a stored value.
     *
     * @param string $name  The name of the value to set
     * @param string $value The value to store
     */
    public function setValue(string $name, string $value): void
    {
        $this->database->query(
            'INSERT INTO config_values SET module = ?, name = ?, value = ? ON DUPLICATE KEY UPDATE value = ?',
            [$this->module, $name, $value, $value],
            "Failed to set stored value {$this->module}:{$name}",
        );
    }

    /**
     * Delete a stored value.
     *
     * @param string $name The name of the value to delete
     */
    public function deleteValue(string $name): void
    {
        $this->database->query(
            'DELETE config_values WHERE module = ? AND name = ?',
            [$this->module, $name],
            "Failed to delete stored value {$this->module}:{$name}",
        );
    }
}
