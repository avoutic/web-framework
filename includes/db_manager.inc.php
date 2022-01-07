<?php
class DBManager extends FrameworkCore
{
    function execute($data)
    {
        $this->verify(isset($data['target_version']), 'No target version specified');
        $this->verify(is_array($data['actions']), 'No action array specified');

        $start_version = $data['target_version'] - 1;

        echo " - Checking current version to match {$start_version}".PHP_EOL;

        $this->check_version($start_version);

        foreach ($data['actions'] as $action)
        {
            $this->verify(isset($action['type']), 'No action type specified');

            if ($action['type'] == 'create_table')
            {
                $this->verify(is_array($action['fields']), 'No fields array specified');
                $this->verify(is_array($action['constraints']), 'No constraints array specified');

                $this->create_table($action['table_name'], $action['fields'], $action['constraints']);
            }
            else
                $this->verify(false, "Unknown action type '{$action['type']}'");
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
        {
            $this->verify(isset($info['type']), 'No field type specified');
            $this->verify(isset($info['name']), 'No field name specified');

            if ($info['type'] == 'foreign_key')
            {
                $this->verify(isset($info['foreign_table']), 'No target for foreign table set');
                $this->verify(isset($info['foreign_field']), 'No target for foreign field set');

                array_push($field_lines, "`{$info['name']}` INT(11) NOT NULL");
                array_push($constraint_lines, "KEY `foreign_{$table_name}_{$info['name']}` (`{$info['name']}`)");
                array_push($constraint_lines, "CONSTRAINT `foreign_{$table_name}_{$info['name']}` FOREIGN KEY (`{$info['name']}`) REFERENCES `{$info['foreign_table']}` (`${info['foreign_field']}`)");
            }
            else if ($info['type'] == 'varchar')
            {
                $this->verify(isset($info['size']), 'No varchar size set');

                array_push($field_lines, "`{$info['name']}` VARCHAR({$info['size']}) NOT NULL");
            }
            else if ($info['type'] == 'int')
            {
                array_push($field_lines, "`{$info['name']}` INT NOT NULL");
            }
            else
                $this->verify(false, "Unknown field type '{$info['type']}'");
        }

        foreach ($constraints as $info)
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

        $lines = array_merge($field_lines, $constraint_lines);
        $lines_fmt = implode(",\n    ", $lines);

        $query = <<<SQL
CREATE TABLE `{$table_name}` (
    {$lines_fmt}
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;

        echo " - Executing:".PHP_EOL.$query.PHP_EOL;

        $params = array();
        $result = $this->query($query, $params);

        if ($result === false)
        {
            echo "   Failed: ";
            $db = $this->get_db();
            echo $db->GetLastError().PHP_EOL;
            exit();
        }
    }
};
?>
