<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
/**
 * Class StoredUserValues.
 *
 * Manages stored user-specific configuration values in the database.
 */
class StoredUserValues
{
    /**
     * StoredUserValues constructor.
     *
     * @param Database $database The database interface
     * @param int      $userId   The ID of the user
     * @param string   $module   The module name for which values are stored
     */
    public function __construct(
        private Database $database,
        private int $userId,
        private string $module,
    ) {}

    /**
     * Get all stored values for the current user and module.
     *
     * @return array<string> An associative array of stored values
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

    /**
     * Check if a specific value exists.
     *
     * @param string $name The name of the value to check
     *
     * @return bool True if the value exists, false otherwise
     */
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

    /**
     * Set a stored value.
     *
     * @param string $name  The name of the value to set
     * @param string $value The value to store
     */
    public function setValue(string $name, string $value): void
    {
        $this->database->query(
            'INSERT user_config_values SET user_id = ?, module = ?, name = ?, value = ? ON DUPLICATE KEY UPDATE value = ?',
            [$this->userId, $this->module, $name, $value, $value],
            "Failed to set stored user value {$this->userId}:{$this->module}:{$name}",
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
            'DELETE user_config_values WHERE user_id = ? AND module = ? AND name = ?',
            [$this->userId, $this->module, $name],
            "Failed to delete stored user value {$this->userId}:{$this->module}:{$name}",
        );
    }
}
