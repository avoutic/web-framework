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

use Psr\Container\ContainerInterface as Container;
use WebFramework\Task\Task;

/**
 * Class DatabaseManager.
 *
 * Manages database operations, including schema changes, version control, and integrity checks.
 */
class DatabaseManager
{
    /**
     * DatabaseManager constructor.
     *
     * @param Container $container    The dependency injection container
     * @param Database  $database     The database interface implementation
     * @param resource  $outputStream The output stream to write to
     */
    public function __construct(
        private Container $container,
        private Database $database,
        private $outputStream = STDOUT
    ) {}

    /**
     * Write a message to the output stream.
     *
     * @param string $message The message to write
     */
    private function write(string $message): void
    {
        fwrite($this->outputStream, $message);
    }

    /**
     * Execute a set of database actions.
     *
     * @param array<mixed, mixed> $data   The actions to execute
     * @param bool                $dryRun Whether to dry run the task
     *
     * @throws \InvalidArgumentException If the input data is invalid
     * @throws \RuntimeException         If an action fails
     */
    public function execute(array $data, bool $dryRun = false): void
    {
        if (!is_array($data['actions']))
        {
            throw new \InvalidArgumentException('No action array specified');
        }

        $this->write(' - Preparing all statements'.PHP_EOL);
        $steps = [];

        foreach ($data['actions'] as $action)
        {
            if (!isset($action['type']))
            {
                throw new \InvalidArgumentException('No action type specified');
            }

            if ($action['type'] == 'create_table')
            {
                if (!isset($action['fields']) || !is_array($action['fields']))
                {
                    throw new \InvalidArgumentException('No fields array specified');
                }

                if (!isset($action['constraints']) || !is_array($action['constraints']))
                {
                    throw new \InvalidArgumentException('No constraints array specified');
                }

                $result = $this->createTable($action['table_name'], $action['fields'], $action['constraints']);
                $steps[] = [
                    'type' => 'query',
                    'data' => $result,
                ];
            }
            elseif ($action['type'] == 'create_trigger')
            {
                if (!isset($action['trigger']) || !is_array($action['trigger']))
                {
                    throw new \InvalidArgumentException('No trigger array specified');
                }

                $result = $this->createTrigger($action['table_name'], $action['trigger']);
                $steps[] = [
                    'type' => 'query',
                    'data' => $result,
                ];
            }
            elseif ($action['type'] == 'add_column')
            {
                if (!is_array($action['field']))
                {
                    throw new \InvalidArgumentException('No field array specified');
                }

                $result = $this->addColumn($action['table_name'], $action['field']);
                $steps[] = [
                    'type' => 'query',
                    'data' => $result,
                ];
            }
            elseif ($action['type'] == 'add_constraint')
            {
                if (!is_array($action['constraint']))
                {
                    throw new \InvalidArgumentException('No constraint array specified');
                }

                $result = $this->addConstraint($action['table_name'], $action['constraint']);
                $steps[] = [
                    'type' => 'query',
                    'data' => $result,
                ];
            }
            elseif ($action['type'] == 'insert_row')
            {
                if (!isset($action['values']) || !is_array($action['values']))
                {
                    throw new \InvalidArgumentException('No values array specified');
                }

                $result = $this->insertRow($action['table_name'], $action['values']);
                $steps[] = [
                    'type' => 'query',
                    'data' => $result,
                ];
            }
            elseif ($action['type'] == 'modify_column_type')
            {
                if (!is_array($action['field']))
                {
                    throw new \InvalidArgumentException('No field array specified');
                }

                $result = $this->modifyColumnType($action['table_name'], $action['field']);
                $steps[] = [
                    'type' => 'query',
                    'data' => $result,
                ];
            }
            elseif ($action['type'] == 'rename_column')
            {
                if (!isset($action['name']))
                {
                    throw new \InvalidArgumentException('No name specified');
                }

                if (!isset($action['new_name']))
                {
                    throw new \InvalidArgumentException('No new_name specified');
                }

                $result = $this->renameColumn($action['table_name'], $action['name'], $action['new_name']);
                $steps[] = [
                    'type' => 'query',
                    'data' => $result,
                ];
            }
            elseif ($action['type'] == 'rename_table')
            {
                if (!isset($action['new_name']))
                {
                    throw new \InvalidArgumentException('No new_name specified');
                }

                $result = $this->renameTable($action['table_name'], $action['new_name']);
                $steps[] = [
                    'type' => 'query',
                    'data' => $result,
                ];
            }
            elseif ($action['type'] == 'raw_query')
            {
                if (!isset($action['query']))
                {
                    throw new \InvalidArgumentException('No query specified');
                }

                if (!isset($action['params']) || !is_array($action['params']))
                {
                    throw new \InvalidArgumentException('No params array specified');
                }

                $result = $this->rawQuery($action['query'], $action['params']);
                $steps[] = [
                    'type' => 'query',
                    'data' => $result,
                ];
            }
            elseif ($action['type'] == 'run_task')
            {
                if (!isset($action['task']))
                {
                    throw new \InvalidArgumentException('No task specified');
                }

                $task = $this->container->get($action['task']);
                if (!$task instanceof Task)
                {
                    throw new \RuntimeException("Task {$action['task']} does not implement Task");
                }

                $steps[] = [
                    'type' => 'task',
                    'data' => $task,
                ];
            }
            else
            {
                throw new \RuntimeException("Unknown action type '{$action['type']}'");
            }
        }

        if ($dryRun)
        {
            $this->write(' - Dry run'.PHP_EOL);

            foreach ($steps as $step)
            {
                if ($step['type'] === 'query')
                {
                    $info = $step['data'];
                    $this->write('   - Would execute:'.PHP_EOL.$info['query'].PHP_EOL);
                }
                elseif ($step['type'] === 'task')
                {
                    $task = $step['data'];
                    $this->write('   - Would execute task:'.PHP_EOL.get_class($task).PHP_EOL);
                }
            }

            return;
        }

        $this->write(' - Executing steps'.PHP_EOL);

        $this->database->startTransaction();

        foreach ($steps as $step)
        {
            if ($step['type'] === 'query')
            {
                $info = $step['data'];
                $this->write('   - Executing:'.PHP_EOL.$info['query'].PHP_EOL);

                try
                {
                    $result = $this->database->query($info['query'], $info['params']);
                }
                catch (\RuntimeException $e)
                {
                    $this->write('   Failed: ');
                    $this->write($this->database->getLastError().PHP_EOL);

                    exit(1);
                }
            }
            elseif ($step['type'] === 'task')
            {
                $task = $step['data'];
                $task->execute();
            }
            else
            {
                throw new \RuntimeException('Unknown step type: '.$step['type']);
            }
        }

        $this->database->commitTransaction();
    }

    /**
     * Generate field statements for table creation or alteration.
     *
     * @param string               $tableName       The name of the table
     * @param array<string, mixed> $info            The field information
     * @param array<string>        $fieldLines      Reference to the array of field lines
     * @param array<string>        $constraintLines Reference to the array of constraint lines
     *
     * @throws \InvalidArgumentException If the field information is invalid
     */
    private function getFieldStatements(string $tableName, array $info, array &$fieldLines, array &$constraintLines): void
    {
        if (!isset($info['type']))
        {
            throw new \InvalidArgumentException('No field type specified');
        }

        if (!isset($info['name']))
        {
            throw new \InvalidArgumentException('No field name specified');
        }

        $dbType = strtoupper($info['type']);
        $null = (isset($info['null']) && $info['null']);

        // First get database type and check requirements
        //
        if ($info['type'] == 'foreign_key')
        {
            if (!isset($info['foreign_table']))
            {
                throw new \InvalidArgumentException('No target for foreign table set');
            }

            if (!isset($info['foreign_field']))
            {
                throw new \InvalidArgumentException('No target for foreign field set');
            }

            $dbType = 'INT(11)';
        }
        elseif (in_array($info['type'], ['varchar', 'char', 'binary', 'varbinary']))
        {
            if (!isset($info['size']))
            {
                throw new \InvalidArgumentException("No {$info['type']} size set");
            }

            $dbType = strtoupper($info['type'])."({$info['size']})";
        }
        elseif (in_array($info['type'], ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'numeric']))
        {
            $size = isset($info['size']) ? "({$info['size']})" : '';
            $dbType = strtoupper($info['type']).$size;
        }
        else
        {
            $dbType = strtoupper($info['type']);
        }

        $nullFmt = $null ? 'NULL' : 'NOT NULL';
        $defaultFmt = '';
        $afterFmt = (isset($info['after'])) ? "AFTER {$info['after']}" : '';

        if (isset($info['default']))
        {
            if (is_array($info['default']) && isset($info['default']['function']))
            {
                $defaultFmt = "DEFAULT {$info['default']['function']}";
            }
            else
            {
                $needsQuotes = in_array($info['type'], [
                    'varchar',
                    'char',
                    'text',
                    'tinytext',
                    'mediumtext',
                    'longtext',
                    'date',
                    'time',
                    'timestamp',
                    'datetime',
                    'enum',
                    'set',
                    'binary',
                    'varbinary',
                    'json',
                    'longblob',
                    'mediumblob',
                    'blob',
                    'tinyblob',
                ]);
                $defaultFmt = $needsQuotes
                    ? "DEFAULT '{$info['default']}'"
                    : "DEFAULT {$info['default']}";
            }
        }

        $str = "`{$info['name']}` {$dbType} {$nullFmt} {$defaultFmt} {$afterFmt}";

        $fieldLines[] = $str;

        // Special post actions
        //
        if ($info['type'] == 'foreign_key')
        {
            $constraintLines[] = "KEY `foreign_{$tableName}_{$info['name']}` (`{$info['name']}`)";
            $onDelete = isset($info['on_delete']) ? "ON DELETE {$info['on_delete']}" : '';
            $onUpdate = isset($info['on_update']) ? "ON UPDATE {$info['on_update']}" : '';

            $line = "CONSTRAINT `foreign_{$tableName}_{$info['name']}` FOREIGN KEY (`{$info['name']}`) REFERENCES `{$info['foreign_table']}` (`{$info['foreign_field']}`) {$onDelete} {$onUpdate}";

            $constraintLines[] = $line;
        }
    }

    /**
     * Generate constraint statements for table creation or alteration.
     *
     * @param string               $tableName       The name of the table
     * @param array<string, mixed> $info            The constraint information
     * @param array<string>        $constraintLines Reference to the array of constraint lines
     *
     * @throws \InvalidArgumentException If the constraint information is invalid
     * @throws \RuntimeException         If an unknown constraint type is encountered
     */
    private function getConstraintStatements(string $tableName, array $info, array &$constraintLines): void
    {
        if (!isset($info['type']))
        {
            throw new \InvalidArgumentException('No constraint type specified');
        }

        if ($info['type'] == 'unique')
        {
            if (!isset($info['values']))
            {
                throw new \InvalidArgumentException('No values for unique specified');
            }

            if (!is_array($info['values']))
            {
                throw new \InvalidArgumentException('Values is not an array');
            }

            $valuesFmt = implode('_', $info['values']);
            $fieldsFmt = implode('`, `', $info['values']);

            $constraintLines[] = "UNIQUE KEY `unique_{$tableName}_{$valuesFmt}` (`{$fieldsFmt}`)";
        }
        elseif ($info['type'] == 'index')
        {
            if (!isset($info['name']))
            {
                throw new \InvalidArgumentException('No name for index specified');
            }

            if (!isset($info['values']))
            {
                throw new \InvalidArgumentException('No values for index specified');
            }

            $valuesFmt = implode('_', $info['values']);
            $fieldsFmt = implode('`, `', $info['values']);

            $constraintLines[] = "INDEX `{$info['name']}` (`{$fieldsFmt}`)";
        }
        else
        {
            throw new \RuntimeException("Unknown constraint type '{$info['type']}'");
        }
    }

    /**
     * Generate a CREATE TABLE query.
     *
     * @param string               $tableName   The name of the table to create
     * @param array<array<string>> $fields      The fields to add to the table
     * @param array<array<string>> $constraints The constraints to add to the table
     *
     * @return array{query: string, params: array<string>} The generated query and its parameters
     */
    private function createTable(string $tableName, array $fields, array $constraints): array
    {
        $fieldLines = [];
        $constraintLines = [];

        // Add id primary key to all tables
        //
        $fieldLines[] = '`id` int(11) NOT NULL AUTO_INCREMENT';
        $constraintLines[] = 'PRIMARY KEY (`id`)';

        foreach ($fields as $info)
        {
            $this->getFieldStatements($tableName, $info, $fieldLines, $constraintLines);
        }

        foreach ($constraints as $info)
        {
            $this->getConstraintStatements($tableName, $info, $constraintLines);
        }

        $lines = array_merge($fieldLines, $constraintLines);
        $linesFmt = implode(",\n    ", $lines);

        $query = <<<SQL
CREATE TABLE `{$tableName}` (
    {$linesFmt}
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * Generate a CREATE TRIGGER query.
     *
     * @param string                $tableName The name of the table for the trigger
     * @param array<string, string> $info      The trigger information
     *
     * @return array{query: string, params: array<string>} The generated query and its parameters
     *
     * @throws \InvalidArgumentException If the trigger information is invalid
     */
    private function createTrigger(string $tableName, array $info): array
    {
        if (!isset($info['name']))
        {
            throw new \InvalidArgumentException('No trigger name specified');
        }

        if (!isset($info['time']))
        {
            throw new \InvalidArgumentException('No trigger time specified');
        }

        if (!isset($info['event']))
        {
            throw new \InvalidArgumentException('No trigger event specified');
        }

        if (!isset($info['action']))
        {
            throw new \InvalidArgumentException('No trigger action specified');
        }

        $query = <<<SQL
CREATE TRIGGER `{$info['name']}` {$info['time']} {$info['event']} ON `{$tableName}`
    FOR EACH ROW {$info['action']}
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * Generate an ALTER TABLE ADD COLUMN query.
     *
     * @param string               $tableName The name of the table to alter
     * @param array<string, mixed> $info      The column information
     *
     * @return array{query: string, params: array<string>} The generated query and its parameters
     */
    private function addColumn(string $tableName, array $info): array
    {
        $fieldLines = [];
        $constraintLines = [];

        $this->getFieldStatements($tableName, $info, $fieldLines, $constraintLines);

        $lines = array_merge($fieldLines, $constraintLines);
        $linesFmt = implode(",\n    ADD ", $lines);

        $query = <<<SQL
ALTER TABLE `{$tableName}`
    ADD {$linesFmt}
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * Generate an ALTER TABLE ADD CONSTRAINT query.
     *
     * @param string               $tableName The name of the table to alter
     * @param array<string, mixed> $info      The constraint information
     *
     * @return array{query: string, params: array<string>} The generated query and its parameters
     */
    private function addConstraint(string $tableName, array $info): array
    {
        $fieldLines = [];
        $constraintLines = [];

        $this->getConstraintStatements($tableName, $info, $constraintLines);

        $lines = array_merge($fieldLines, $constraintLines);
        $linesFmt = implode(",\n    ADD ", $lines);

        $query = <<<SQL
ALTER TABLE `{$tableName}`
    ADD {$linesFmt}
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * Generate an INSERT query.
     *
     * @param string                     $tableName The name of the table to insert into
     * @param array<string, null|string> $values    The values to insert
     *
     * @return array{query: string, params: array<string>} The generated query and its parameters
     */
    private function insertRow(string $tableName, array $values): array
    {
        $fieldsFmt = '';
        $params = [];
        $first = true;

        foreach ($values as $key => $value)
        {
            if (!$first)
            {
                $fieldsFmt .= ', ';
            }
            else
            {
                $first = false;
            }

            if ($value === null)
            {
                $fieldsFmt .= "`{$key}` = NULL";
            }
            else
            {
                $fieldsFmt .= "`{$key}` = ?";
                $params[] = $value;
            }
        }

        $query = <<<SQL
INSERT INTO `{$tableName}`
    SET {$fieldsFmt}
SQL;

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * Generate a raw SQL query.
     *
     * @param string        $query  The raw SQL query
     * @param array<string> $params The query parameters
     *
     * @return array{query: string, params: array<string>} The query and its parameters
     */
    private function rawQuery(string $query, array $params): array
    {
        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * Generate an ALTER TABLE MODIFY COLUMN query.
     *
     * @param string               $tableName The name of the table to alter
     * @param array<string, mixed> $info      The column information
     *
     * @return array{query: string, params: array<string>} The generated query and its parameters
     */
    private function modifyColumnType(string $tableName, array $info): array
    {
        $fieldLines = [];
        $constraintLines = [];

        $this->getFieldStatements($tableName, $info, $fieldLines, $constraintLines);

        $lines = array_merge($fieldLines, $constraintLines);
        $linesFmt = implode(",\n    MODIFY ", $lines);

        $query = <<<SQL
ALTER TABLE `{$tableName}`
    MODIFY {$linesFmt}
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * Generate an ALTER TABLE RENAME COLUMN query.
     *
     * @param string $tableName   The name of the table to alter
     * @param string $currentName The current name of the column
     * @param string $newName     The new name for the column
     *
     * @return array{query: string, params: array<string>} The generated query and its parameters
     */
    private function renameColumn(string $tableName, string $currentName, string $newName): array
    {
        $query = <<<SQL
ALTER TABLE `{$tableName}`
    RENAME COLUMN `{$currentName}` TO `{$newName}`
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }

    /**
     * Generate an ALTER TABLE RENAME TO query.
     *
     * @param string $tableName The current name of the table
     * @param string $newName   The new name for the table
     *
     * @return array{query: string, params: array<string>} The generated query and its parameters
     */
    private function renameTable(string $tableName, string $newName): array
    {
        $query = <<<SQL
ALTER TABLE `{$tableName}`
    RENAME TO `{$newName}`
SQL;

        $params = [];

        return [
            'query' => $query,
            'params' => $params,
        ];
    }
}
