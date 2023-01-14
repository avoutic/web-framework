<?php

namespace WebFramework\Core;

class DatabaseManager extends FrameworkCore
{
    protected function get_stored_values(): StoredValues
    {
        return new StoredValues('db');
    }

    public function is_initialized(): bool
    {
        $database = $this->get_db();

        return $database->table_exists('config_values');
    }

    public function calculate_hash(): string
    {
        // Get tables
        //
        $query = <<<'SQL'
        SHOW TABLES
SQL;

        $params = [];

        $result = $this->query($query, $params);
        $this->verify($result !== false, 'Failed to retrieve tables');

        $db_def = '';

        foreach ($result as $row)
        {
            $table_name = reset($row);

            // Get tables
            //
            $query = <<<SQL
            SHOW CREATE TABLE {$table_name}
SQL;

            $params = [];

            $result = $this->query($query, $params);
            $this->verify($result !== false, 'Failed to retrieve create table');

            $statement = $result->fields['Create Table'].PHP_EOL;

            // Filter out AUTO_INCREMENT intrializer
            //
            $statement = preg_replace('/ AUTO_INCREMENT=\d+/', '', $statement);

            $db_def .= $statement;
        }

        return sha1($db_def);
    }

    public function verify_hash(): bool
    {
        $stored_hash = $this->get_stored_hash();
        $actual_hash = $this->calculate_hash();

        return $stored_hash === $actual_hash;
    }

    public function get_stored_hash(): string
    {
        // Retrieve hash
        //
        $db_values = $this->get_stored_values();

        return $db_values->get_value('app_db_hash', '');
    }

    public function update_stored_hash(): void
    {
        $actual_hash = $this->calculate_hash();

        $db_values = $this->get_stored_values();

        $db_values->set_value('app_db_hash', $actual_hash);
    }

    /**
     * @param array{target_version: int, actions: array<array<mixed>>} $data
     */
    public function execute(array $data, bool $ignore_version = false): void
    {
        $this->verify(is_array($data['actions']), 'No action array specified');

        if (!$ignore_version)
        {
            $this->verify(isset($data['target_version']), 'No target version specified');

            $start_version = $data['target_version'] - 1;

            echo " - Checking current version to match {$start_version}".PHP_EOL;

            $this->check_version($start_version);
        }

        echo ' - Preparing all statements'.PHP_EOL;
        $queries = [];

        foreach ($data['actions'] as $action)
        {
            $this->verify(isset($action['type']), 'No action type specified');

            if ($action['type'] == 'create_table')
            {
                $this->verify(isset($action['fields']) && is_array($action['fields']), 'No fields array specified');
                $this->verify(isset($action['constraints']) && is_array($action['constraints']), 'No constraints array specified');

                $result = $this->create_table($action['table_name'], $action['fields'], $action['constraints']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'create_trigger')
            {
                $this->verify(isset($action['trigger']) && is_array($action['trigger']), 'No trigger array specified');

                $result = $this->create_trigger($action['table_name'], $action['trigger']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'add_column')
            {
                $this->verify(is_array($action['field']), 'No field array specified');
                $result = $this->add_column($action['table_name'], $action['field']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'add_constraint')
            {
                $this->verify(is_array($action['constraint']), 'No constraint array specified');
                $result = $this->add_constraint($action['table_name'], $action['constraint']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'insert_row')
            {
                $this->verify(isset($action['values']) && is_array($action['values']), 'No values array specified');

                $result = $this->insert_row($action['table_name'], $action['values']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'modify_column_type')
            {
                $this->verify(is_array($action['field']), 'No field array specified');
                $result = $this->modify_column_type($action['table_name'], $action['field']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'rename_column')
            {
                $this->verify(isset($action['name']), 'No name specified');
                $this->verify(isset($action['new_name']), 'No new_name specified');
                $result = $this->rename_column($action['table_name'], $action['name'], $action['new_name']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'rename_table')
            {
                $this->verify(isset($action['new_name']), 'No new_name specified');
                $result = $this->rename_table($action['table_name'], $action['new_name']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'raw_query')
            {
                $this->verify(isset($action['query']), 'No query specified');
                $this->verify(isset($action['params']) && is_array($action['params']), 'No params array specified');

                $result = $this->raw_query($action['query'], $action['params']);
                $queries[] = $result;
            }
            else
            {
                $this->verify(false, "Unknown action type '{$action['type']}'");
            }
        }

        echo ' - Executing queries'.PHP_EOL;

        foreach ($queries as $info)
        {
            echo '   - Executing:'.PHP_EOL.$info['query'].PHP_EOL;

            $result = $this->query($info['query'], $info['params']);

            if ($result === false)
            {
                echo '   Failed: ';
                $db = $this->get_db();
                echo $db->get_last_error().PHP_EOL;

                exit();
            }
        }

        if ($ignore_version)
        {
            return;
        }

        echo " - Updating version to {$data['target_version']}".PHP_EOL;

        $this->set_version($data['target_version']);
    }

    public function get_current_version(): int
    {
        $db_values = $this->get_stored_values();

        return (int) $db_values->get_value('app_db_version', '0');
    }

    private function check_version(int $app_db_version): void
    {
        $current_version = $this->get_current_version();

        $this->verify($current_version == $app_db_version, "DB version '{$current_version}' does not match requested version '{$app_db_version}'");
    }

    private function set_version(int $to): void
    {
        $db_values = $this->get_stored_values();

        $db_values->set_value('app_db_version', (string) $to);

        $this->update_stored_hash();
    }

    /**
     * @param array<string> $info
     * @param array<string> $field_lines
     * @param array<string> $constraint_lines
     */
    private function get_field_statements(string $table_name, array $info, array &$field_lines, array &$constraint_lines): void
    {
        $this->verify(isset($info['type']), 'No field type specified');
        $this->verify(isset($info['name']), 'No field name specified');

        $db_type = strtoupper($info['type']);
        $null = (isset($info['null']) && $info['null']);

        // First get database type and check requirements
        //
        if ($info['type'] == 'foreign_key')
        {
            $this->verify(isset($info['foreign_table']), 'No target for foreign table set');
            $this->verify(isset($info['foreign_field']), 'No target for foreign field set');

            $db_type = 'INT(11)';
        }
        elseif ($info['type'] == 'varchar')
        {
            $this->verify(isset($info['size']), 'No varchar size set');

            $db_type = "VARCHAR({$info['size']})";
        }

        $null_fmt = $null ? 'NULL' : 'NOT NULL';
        $default_fmt = (isset($info['default'])) ? "DEFAULT {$info['default']}" : '';
        $after_fmt = (isset($info['after'])) ? "AFTER {$info['after']}" : '';

        // Special changes to standard flow
        //
        if ($info['type'] == 'varchar' || $info['type'] == 'text' || $info['type'] == 'tinytext')
        {
            if (isset($info['default']))
            {
                $default_fmt = "DEFAULT '{$info['default']}'";
            }
        }

        $str = "`{$info['name']}` {$db_type} {$null_fmt} {$default_fmt} {$after_fmt}";

        $field_lines[] = $str;

        // Special post actions
        //
        if ($info['type'] == 'foreign_key')
        {
            $constraint_lines[] = "KEY `foreign_{$table_name}_{$info['name']}` (`{$info['name']}`)";
            $on_delete = isset($info['on_delete']) ? "ON DELETE {$info['on_delete']}" : '';
            $on_update = isset($info['on_update']) ? "ON UPDATE {$info['on_update']}" : '';

            $line = "CONSTRAINT `foreign_{$table_name}_{$info['name']}` FOREIGN KEY (`{$info['name']}`) REFERENCES `{$info['foreign_table']}` (`${info['foreign_field']}`) {$on_delete} {$on_update}";

            $constraint_lines[] = $line;
        }
    }

    /**
     * @param array<mixed>  $info
     * @param array<string> $constraint_lines
     */
    private function get_constraint_statements(string $table_name, array $info, array &$constraint_lines): void
    {
        $this->verify(isset($info['type']), 'No constraint type specified');

        if ($info['type'] == 'unique')
        {
            $this->verify(isset($info['values']), 'No values for unique specified');
            $this->verify(is_array($info['values']), 'Values is not an array');

            $values_fmt = implode('_', $info['values']);
            $fields_fmt = implode('`, `', $info['values']);

            $constraint_lines[] = "UNIQUE KEY `unique_{$table_name}_{$values_fmt}` (`{$fields_fmt}`)";
        }
        else
        {
            $this->verify(false, "Unknown constraint type '{$info['type']}'");
        }
    }

    /**
     * @param array<array<string>> $fields
     * @param array<array<string>> $constraints
     *
     * @return array{query: string, params: array<string>}
     */
    private function create_table(string $table_name, array $fields, array $constraints): array
    {
        $field_lines = [];
        $constraint_lines = [];

        // Add id primary key to all tables
        //
        $field_lines[] = '`id` int(11) NOT NULL AUTO_INCREMENT';
        $constraint_lines[] = 'PRIMARY KEY (`id`)';

        foreach ($fields as $info)
        {
            $this->get_field_statements($table_name, $info, $field_lines, $constraint_lines);
        }

        foreach ($constraints as $info)
        {
            $this->get_constraint_statements($table_name, $info, $constraint_lines);
        }

        $lines = array_merge($field_lines, $constraint_lines);
        $lines_fmt = implode(",\n    ", $lines);

        $query = <<<SQL
CREATE TABLE `{$table_name}` (
    {$lines_fmt}
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * @param array<string> $info
     *
     * @return array{query: string, params: array<string>}
     */
    private function create_trigger(string $table_name, array $info): array
    {
        $this->verify(isset($info['name']), 'No trigger name specified');
        $this->verify(isset($info['time']), 'No trigger time specified');
        $this->verify(isset($info['event']), 'No trigger event specified');
        $this->verify(isset($info['action']), 'No trigger action specified');

        $query = <<<SQL
CREATE TRIGGER `{$info['name']}` {$info['time']} {$info['event']} ON `{$table_name}`
    FOR EACH ROW {$info['action']}
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * @param array<string> $info
     *
     * @return array{query: string, params: array<string>}
     */
    private function add_column(string $table_name, array $info): array
    {
        $field_lines = [];
        $constraint_lines = [];

        $this->get_field_statements($table_name, $info, $field_lines, $constraint_lines);

        $lines = array_merge($field_lines, $constraint_lines);
        $lines_fmt = implode(",\n    ADD ", $lines);

        $query = <<<SQL
ALTER TABLE `{$table_name}`
    ADD {$lines_fmt}
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * @param array<string> $info
     *
     * @return array{query: string, params: array<string>}
     */
    private function add_constraint(string $table_name, array $info): array
    {
        $field_lines = [];
        $constraint_lines = [];

        $this->get_constraint_statements($table_name, $info, $constraint_lines);

        $lines = array_merge($field_lines, $constraint_lines);
        $lines_fmt = implode(",\n    ADD ", $lines);

        $query = <<<SQL
ALTER TABLE `{$table_name}`
    ADD {$lines_fmt}
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * @param array<string, null|string> $values
     *
     * @return array{query: string, params: array<string>}
     */
    private function insert_row(string $table_name, array $values): array
    {
        $fields_fmt = '';
        $params = [];
        $first = true;

        foreach ($values as $key => $value)
        {
            if (!$first)
            {
                $fields_fmt .= ', ';
            }
            else
            {
                $first = false;
            }

            if ($value === null)
            {
                $fields_fmt .= "`{$key}` = NULL";
            }
            else
            {
                $fields_fmt .= "`{$key}` = ?";
                $params[] = $value;
            }
        }

        $query = <<<SQL
INSERT INTO `{$table_name}`
    SET {$fields_fmt}
SQL;

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * @param array<string> $params
     *
     * @return array{query: string, params: array<string>}
     */
    private function raw_query(string $query, array $params): array
    {
        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * @param array<string> $info
     *
     * @return array{query: string, params: array<string>}
     */
    private function modify_column_type(string $table_name, array $info): array
    {
        $field_lines = [];
        $constraint_lines = [];

        $this->get_field_statements($table_name, $info, $field_lines, $constraint_lines);

        $lines = array_merge($field_lines, $constraint_lines);
        $lines_fmt = implode(",\n    MODIFY ", $lines);

        $query = <<<SQL
ALTER TABLE `{$table_name}`
    MODIFY {$lines_fmt}
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * @return array{query: string, params: array<string>}
     */
    private function rename_column(string $table_name, string $current_name, string $new_name): array
    {
        $query = <<<SQL
ALTER TABLE `{$table_name}`
    RENAME COLUMN `{$current_name}` TO `{$new_name}`
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * @return array{query: string, params: array<string>}
     */
    private function rename_table(string $table_name, string $new_name): array
    {
        $query = <<<SQL
ALTER TABLE `{$table_name}`
    RENAME TO `{$new_name}`
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }
}
