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

// Not a FrameworkCore object because it needs to operate before all components have been loaded.
// Therefore there is no StoreValue datacore object either.
//
class StoredValues
{
    public function __construct(
        private Database $database,
        private string $module,
    ) {
    }

    /**
     * @return array<string>
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

    public function setValue(string $name, string $value): void
    {
        $this->database->query(
            'INSERT INTO config_values SET module = ?, name = ?, value = ? ON DUPLICATE KEY UPDATE value = ?',
            [$this->module, $name, $value, $value],
            "Failed to set stored value {$this->module}:{$name}",
        );
    }

    public function deleteValue(string $name): void
    {
        $this->database->query(
            'DELETE config_values WHERE module = ? AND name = ?',
            [$this->module, $name],
            "Failed to delete stored value {$this->module}:{$name}",
        );
    }
}
