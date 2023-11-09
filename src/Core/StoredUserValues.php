<?php

namespace WebFramework\Core;

/*
CREATE TABLE IF NOT EXISTS stored_user_values (
     user_id INT NOT NULL REFERENCES users(id),
     module VARCHAR(255) NOT NULL,
     name VARCHAR(255) NOT NULL,
     value VARCHAR(255) NOT NULL,
     UNIQUE KEY `user_mod_name` (user_id,module,name)
);
*/
class StoredUserValues
{
    public function __construct(
        private Database $database,
        private int $userId,
        private string $module,
    ) {
    }

    /**
     * @return array<string>
     */
    public function getValues(): array
    {
        $result = $this->database->query(
            'SELECT name, value FROM user_config_values WHERE user_id = ? AND module = ?',
            [$this->userId, $this->module],
            "Failed to retrieve stored user values for {$this->userId}:{$this->module}",
        );

        $info = [];

        foreach ($result as $row)
        {
            $info[$row['name']] = $row['value'];
        }

        return $info;
    }

    public function valueExists(string $name): bool
    {
        $result = $this->database->query(
            'SELECT value FROM user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            [$this->userId, $this->module, $name],
            "Failed to retrieve stored user value for {$this->userId}:{$this->module}:{$name}",
        );

        if ($result->RecordCount() == 0)
        {
            return false;
        }

        return true;
    }

    public function getValue(string $name, string $default = ''): string
    {
        $result = $this->database->query(
            'SELECT value FROM user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            [$this->userId, $this->module, $name],
            "Failed to retrieve stored user value {$this->userId}:{$this->module}:{$name}",
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
            'INSERT user_config_values SET user_id = ?, module = ?, name = ?, value = ? ON DUPLICATE KEY UPDATE value = ?',
            [$this->userId, $this->module, $name, $value, $value],
            "Failed to set stored user value {$this->userId}:{$this->module}:{$name}",
        );
    }

    public function deleteValue(string $name): void
    {
        $this->database->query(
            'DELETE user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            [$this->userId, $this->module, $name],
            "Failed to delete stored user value {$this->userId}:{$this->module}:{$name}",
        );
    }
}
