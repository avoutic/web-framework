<?php
class DBManager extends FrameworkCore
{
    function calculate_hash()
    {
        // Get tables
        //
        $query = <<<SQL
        SHOW TABLES
SQL;

        $params = array();

        $result = $this->query($query, $params);
        $this->verify($result !== false, 'Failed to retrieve tables');

        $db_def = '';

        foreach ($result as $row)
        {
            $table_name = $row[0];

            // Get tables
            //
            $query = <<<SQL
            SHOW CREATE TABLE {$table_name}
SQL;

            $params = array();

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

    function verify_hash()
    {
        $stored_hash = $this->get_stored_hash();
        $actual_hash = $this->calculate_hash();

        return $stored_hash === $actual_hash;
    }

    function get_stored_hash()
    {
        // Retrieve hash
        //
        $query = <<<SQL
        SELECT value
        FROM config_values
        WHERE module = 'db' AND
              name = 'app_db_hash'
SQL;

        $params = array();

        $result = $this->query($query, $params);
        $this->verify($result !== false, 'Failed to retrieve App DB hash');

        if ($result->RecordCount() == 0)
            return '';

        return $result->fields['value'];
    }

    function update_stored_hash()
    {
        $actual_hash = $this->calculate_hash();

        // Retrieve hash
        //
        $query = <<<SQL
        UPDATE config_values
        SET value = ?
        WHERE module = 'db' AND
              name = 'app_db_hash'
SQL;

        $params = array($actual_hash);

        $result = $this->query($query, $params);
        $this->verify($result !== false, 'Failed to update App DB hash');
    }

    function execute($data)
    {
        $this->verify(isset($data['target_version']), 'No target version specified');
        $this->verify(is_array($data['actions']), 'No action array specified');

        $start_version = $data['target_version'] - 1;

        echo " - Checking current version to match {$start_version}".PHP_EOL;

        $this->check_version($start_version);

        echo " - Preparing all statements".PHP_EOL;
        $queries = array();

        foreach ($data['actions'] as $action)
        {
            $this->verify(isset($action['type']), 'No action type specified');

            if ($action['type'] == 'create_table')
            {
                $this->verify(isset($action['fields']) && is_array($action['fields']), 'No fields array specified');
                $this->verify(isset($action['constraints']) && is_array($action['constraints']), 'No constraints array specified');

                $result = $this->create_table($action['table_name'], $action['fields'], $action['constraints']);
                array_push($queries, $result);
            }
            else if ($action['type'] == 'create_trigger')
            {
                $this->verify(isset($action['trigger']) && is_array($action['trigger']), 'No trigger array specified');

                $result = $this->create_trigger($action['table_name'], $action['trigger']);
                array_push($queries, $result);
            }
            else if ($action['type'] == 'add_column')
            {
                $this->verify(is_array($action['field']), 'No field array specified');
                $result = $this->add_column($action['table_name'], $action['field']);
                array_push($queries, $result);
            }
            else if ($action['type'] == 'rename_column')
            {
                $this->verify(isset($action['name']), 'No name specified');
                $this->verify(isset($action['new_name']), 'No new_name specified');
                $result = $this->rename_column($action['table_name'], $action['name'], $action['new_name']);
                array_push($queries, $result);
            }
            else if ($action['type'] == 'rename_table')
            {
                $this->verify(isset($action['new_name']), 'No new_name specified');
                $result = $this->rename_table($action['table_name'], $action['new_name']);
                array_push($queries, $result);
            }
            else
                $this->verify(false, "Unknown action type '{$action['type']}'");
        }

        echo " - Executing queries".PHP_EOL;

        foreach ($queries as $info)
        {
            echo "   - Executing:".PHP_EOL.$info['query'].PHP_EOL;

            $result = $this->query($info['query'], $info['params']);

            if ($result === false)
            {
                echo "   Failed: ";
                $db = $this->get_db();
                echo $db->GetLastError().PHP_EOL;
                exit();
            }
        }

        echo " - Updating version to {$data['target_version']}".PHP_EOL;

        $this->set_version($data['target_version']);
    }

    function get_current_version()
    {
        // Check version
        //
        $query = <<<SQL
        SELECT value
        FROM config_values
        WHERE module = 'db' AND
              name = 'app_db_version'
SQL;

        $params = array();

        $result = $this->query($query, $params);
        $this->verify($result !== false, 'Failed to retrieve App DB version');

        return $result->fields['value'];
    }

    private function check_version($app_db_version)
    {
        $current_version = $this->get_current_version();

        $this->verify($current_version == $app_db_version, "DB version '{$current_version}' does not match requested version '{$app_db_version}'");
    }

    private function set_version($to)
    {
        // Update version
        //
        $query = <<<SQL
        UPDATE config_values
        SET value = ?
        WHERE module = 'db' AND
              name = 'app_db_version'
SQL;

        $params = array($to);

        $result = $this->query($query, $params);
        $this->verify($result !== false, 'Failed to update App DB version');

        $this->update_stored_hash();
    }

    private function get_field_statements($table_name, $info, &$field_lines, &$constraint_lines)
    {
        $this->verify(isset($info['type']), 'No field type specified');
        $this->verify(isset($info['name']), 'No field name specified');

        $db_type = '';
        $null = (isset($info['null']) && $info['null']);

        // First get database type and check requirements
        //
        if ($info['type'] == 'boolean')
            $db_type = 'BOOLEAN';
        else if ($info['type'] == 'foreign_key')
        {
            $this->verify(isset($info['foreign_table']), 'No target for foreign table set');
            $this->verify(isset($info['foreign_field']), 'No target for foreign field set');

            $db_type = 'INT(11)';
        }
        else if ($info['type'] == 'varchar')
        {
            $this->verify(isset($info['size']), 'No varchar size set');

            $db_type = "VARCHAR({$info['size']})";
        }
        else if ($info['type'] == 'int')
            $db_type = 'INT';
        else if ($info['type'] == 'text')
            $db_type = 'TEXT';
        else
            $this->verify(false, "Unhandled field type '{$info['type']}'");

        $null_fmt = $null ? 'NULL' : 'NOT NULL';
        $default_fmt = (isset($info['default'])) ? "DEFAULT {$info['default']}" : '';
        $after_fmt = (isset($info['after'])) ? "AFTER {$info['after']}" : '';

        // Special changes to standard flow
        //
        if ($info['type'] == 'varchar')
        {
            if (isset($info['default']))
                $default_fmt = "DEFAULT '{$info['default']}'";
        }

        $str = "`{$info['name']}` {$db_type} {$null_fmt} {$default_fmt} {$after_fmt}";

        array_push($field_lines, $str);

        // Special post actions
        //
        if ($info['type'] == 'foreign_key')
        {
            array_push($constraint_lines, "KEY `foreign_{$table_name}_{$info['name']}` (`{$info['name']}`)");
            array_push($constraint_lines, "CONSTRAINT `foreign_{$table_name}_{$info['name']}` FOREIGN KEY (`{$info['name']}`) REFERENCES `{$info['foreign_table']}` (`${info['foreign_field']}`)");
        }
    }

    private function get_constraint_statements($table_name, $info, &$constraint_lines)
    {
        $this->verify(isset($info['type']), 'No constraint type specified');

        if ($info['type'] == 'unique')
        {
            $this->verify(isset($info['values']), 'No values for unique specified');
            $this->verify(is_array($info['values']), 'Values is not an array');

            $values_fmt = implode('_', $info['values']);
            $fields_fmt = implode('`, `', $info['values']);

            array_push($constraint_lines, "UNIQUE KEY `unique_{$table_name}_{$values_fmt}` (`{$fields_fmt}`)");
        }
        else
            $this->verify(false, "Unknown constraint type '{$info['type']}'");
    }

    private function create_table($table_name, $fields, $constraints)
    {
        $field_lines = array();
        $constraint_lines = array();

        // Add id primary key to all tables
        //
        array_push($field_lines, "`id` int(11) NOT NULL AUTO_INCREMENT");
        array_push($constraint_lines, "PRIMARY KEY (`id`)");

        foreach ($fields as $info)
            $this->get_field_statements($table_name, $info, $field_lines, $constraint_lines);

        foreach ($constraints as $info)
            $this->get_constraint_statements($table_name, $info, $constraint_lines);

        $lines = array_merge($field_lines, $constraint_lines);
        $lines_fmt = implode(",\n    ", $lines);

        $query = <<<SQL
CREATE TABLE `{$table_name}` (
    {$lines_fmt}
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;

        $params = array();

        return array(
            'query' => $query,
            'params' => $params,
        );
    }

    private function create_trigger($table_name, $info)
    {
        $this->verify(isset($info['name']), 'No trigger name specified');
        $this->verify(isset($info['time']), 'No trigger time specified');
        $this->verify(isset($info['event']), 'No trigger event specified');
        $this->verify(isset($info['action']), 'No trigger action specified');

        $query = <<<SQL
CREATE TRIGGER `{$info['name']}` {$info['time']} {$info['event']} ON `{$table_name}`
    FOR EACH ROW {$info['action']}
SQL;

        $params = array();

        return array(
            'query' => $query,
            'params' => $params,
        );
    }

    private function add_column($table_name, $info)
    {
        $field_lines = array();
        $constraint_lines = array();

        $this->get_field_statements($table_name, $info, $field_lines, $constraint_lines);

        $lines = array_merge($field_lines, $constraint_lines);
        $lines_fmt = implode(",\n    ADD ", $lines);

        $query = <<<SQL
ALTER TABLE `{$table_name}`
    ADD {$lines_fmt}
SQL;

        $params = array();

        return array(
            'query' => $query,
            'params' => $params,
        );
    }

    private function rename_column($table_name, $current_name, $new_name)
    {
        $query = <<<SQL
ALTER TABLE `{$table_name}`
    RENAME COLUMN `{$current_name}` TO `{$new_name}`
SQL;

        $params = array();

        return array(
            'query' => $query,
            'params' => $params,
        );
    }

    private function rename_table($table_name, $new_name)
    {
        $query = <<<SQL
ALTER TABLE `{$table_name}`
    RENAME TO `{$new_name}`
SQL;

        $params = array();

        return array(
            'query' => $query,
            'params' => $params,
        );
    }
};
?>
