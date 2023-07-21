<?php

namespace WebFramework\Core;

class DatabaseManager
{
    public function __construct(
        private AssertService $assertService,
        private Database $database,
        private StoredValues $storedValues,
    ) {
    }

    public function isInitialized(): bool
    {
        return $this->database->tableExists('config_values');
    }

    public function calculateHash(): string
    {
        // Get tables
        //
        $query = <<<'SQL'
        SHOW TABLES
SQL;

        $params = [];

        $result = $this->database->query($query, $params);
        $this->assertService->verify($result !== false, 'Failed to retrieve tables');

        $dbDef = '';

        foreach ($result as $row)
        {
            $tableName = reset($row);

            // Get tables
            //
            $query = <<<SQL
            SHOW CREATE TABLE {$tableName}
SQL;

            $params = [];

            $result = $this->database->query($query, $params);
            $this->assertService->verify($result !== false, 'Failed to retrieve create table');

            $statement = $result->fields['Create Table'].PHP_EOL;

            // Filter out AUTO_INCREMENT intrializer
            //
            $statement = preg_replace('/ AUTO_INCREMENT=\d+/', '', $statement);

            $dbDef .= $statement;
        }

        return sha1($dbDef);
    }

    public function verifyHash(): bool
    {
        $storedHash = $this->getStoredHash();
        $actualHash = $this->calculateHash();

        return $storedHash === $actualHash;
    }

    public function getStoredHash(): string
    {
        // Retrieve hash
        //
        return $this->storedValues->getValue('app_db_hash', '');
    }

    public function updateStoredHash(): void
    {
        $actualHash = $this->calculateHash();

        $this->storedValues->setValue('app_db_hash', $actualHash);
    }

    /**
     * @param array{target_version: int, actions: array<array<mixed>>} $data
     */
    public function execute(array $data, bool $ignoreVersion = false): void
    {
        $this->assertService->verify(is_array($data['actions']), 'No action array specified');

        if (!$ignoreVersion)
        {
            $this->assertService->verify(isset($data['target_version']), 'No target version specified');

            $startVersion = $data['target_version'] - 1;

            echo " - Checking current version to match {$startVersion}".PHP_EOL;

            $this->checkVersion($startVersion);
        }

        echo ' - Preparing all statements'.PHP_EOL;
        $queries = [];

        foreach ($data['actions'] as $action)
        {
            $this->assertService->verify(isset($action['type']), 'No action type specified');

            if ($action['type'] == 'create_table')
            {
                $this->assertService->verify(isset($action['fields']) && is_array($action['fields']), 'No fields array specified');
                $this->assertService->verify(isset($action['constraints']) && is_array($action['constraints']), 'No constraints array specified');

                $result = $this->createTable($action['table_name'], $action['fields'], $action['constraints']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'create_trigger')
            {
                $this->assertService->verify(isset($action['trigger']) && is_array($action['trigger']), 'No trigger array specified');

                $result = $this->createTrigger($action['table_name'], $action['trigger']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'add_column')
            {
                $this->assertService->verify(is_array($action['field']), 'No field array specified');
                $result = $this->addColumn($action['table_name'], $action['field']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'add_constraint')
            {
                $this->assertService->verify(is_array($action['constraint']), 'No constraint array specified');
                $result = $this->addConstraint($action['table_name'], $action['constraint']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'insert_row')
            {
                $this->assertService->verify(isset($action['values']) && is_array($action['values']), 'No values array specified');

                $result = $this->insertRow($action['table_name'], $action['values']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'modify_column_type')
            {
                $this->assertService->verify(is_array($action['field']), 'No field array specified');
                $result = $this->modifyColumnType($action['table_name'], $action['field']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'rename_column')
            {
                $this->assertService->verify(isset($action['name']), 'No name specified');
                $this->assertService->verify(isset($action['new_name']), 'No new_name specified');
                $result = $this->renameColumn($action['table_name'], $action['name'], $action['new_name']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'rename_table')
            {
                $this->assertService->verify(isset($action['new_name']), 'No new_name specified');
                $result = $this->renameTable($action['table_name'], $action['new_name']);
                $queries[] = $result;
            }
            elseif ($action['type'] == 'raw_query')
            {
                $this->assertService->verify(isset($action['query']), 'No query specified');
                $this->assertService->verify(isset($action['params']) && is_array($action['params']), 'No params array specified');

                $result = $this->rawQuery($action['query'], $action['params']);
                $queries[] = $result;
            }
            else
            {
                throw new \RuntimeException("Unknown action type '{$action['type']}'");
            }
        }

        echo ' - Executing queries'.PHP_EOL;

        $this->database->startTransaction();

        foreach ($queries as $info)
        {
            echo '   - Executing:'.PHP_EOL.$info['query'].PHP_EOL;

            $result = $this->database->query($info['query'], $info['params']);

            if ($result === false)
            {
                echo '   Failed: ';
                echo $this->database->getLastError().PHP_EOL;

                exit();
            }
        }

        if ($ignoreVersion)
        {
            return;
        }

        echo " - Updating version to {$data['target_version']}".PHP_EOL;

        $this->setVersion($data['target_version']);

        $this->database->commitTransaction();
    }

    public function getCurrentVersion(): int
    {
        return (int) $this->storedValues->getValue('app_db_version', '0');
    }

    private function checkVersion(int $appDbVersion): void
    {
        $currentVersion = $this->getCurrentVersion();

        $this->assertService->verify($currentVersion == $appDbVersion, "DB version '{$currentVersion}' does not match requested version '{$appDbVersion}'");
    }

    private function setVersion(int $to): void
    {
        $this->storedValues->setValue('app_db_version', (string) $to);

        $this->updateStoredHash();
    }

    /**
     * @param array<string> $info
     * @param array<string> $fieldLines
     * @param array<string> $constraintLines
     */
    private function getFieldStatements(string $tableName, array $info, array &$fieldLines, array &$constraintLines): void
    {
        $this->assertService->verify(isset($info['type']), 'No field type specified');
        $this->assertService->verify(isset($info['name']), 'No field name specified');

        $dbType = strtoupper($info['type']);
        $null = (isset($info['null']) && $info['null']);

        // First get database type and check requirements
        //
        if ($info['type'] == 'foreign_key')
        {
            $this->assertService->verify(isset($info['foreign_table']), 'No target for foreign table set');
            $this->assertService->verify(isset($info['foreign_field']), 'No target for foreign field set');

            $dbType = 'INT(11)';
        }
        elseif ($info['type'] == 'varchar')
        {
            $this->assertService->verify(isset($info['size']), 'No varchar size set');

            $dbType = "VARCHAR({$info['size']})";
        }

        $nullFmt = $null ? 'NULL' : 'NOT NULL';
        $defaultFmt = (isset($info['default'])) ? "DEFAULT {$info['default']}" : '';
        $afterFmt = (isset($info['after'])) ? "AFTER {$info['after']}" : '';

        // Special changes to standard flow
        //
        if ($info['type'] == 'varchar' || $info['type'] == 'text' || $info['type'] == 'tinytext')
        {
            if (isset($info['default']))
            {
                $defaultFmt = "DEFAULT '{$info['default']}'";
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
     * @param array<mixed>  $info
     * @param array<string> $constraintLines
     */
    private function getConstraintStatements(string $tableName, array $info, array &$constraintLines): void
    {
        $this->assertService->verify(isset($info['type']), 'No constraint type specified');

        if ($info['type'] == 'unique')
        {
            $this->assertService->verify(isset($info['values']), 'No values for unique specified');
            $this->assertService->verify(is_array($info['values']), 'Values is not an array');

            $valuesFmt = implode('_', $info['values']);
            $fieldsFmt = implode('`, `', $info['values']);

            $constraintLines[] = "UNIQUE KEY `unique_{$tableName}_{$valuesFmt}` (`{$fieldsFmt}`)";
        }
        else
        {
            throw new \RuntimeException("Unknown constraint type '{$info['type']}'");
        }
    }

    /**
     * @param array<array<string>> $fields
     * @param array<array<string>> $constraints
     *
     * @return array{query: string, params: array<string>}
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
     * @param array<string> $info
     *
     * @return array{query: string, params: array<string>}
     */
    private function createTrigger(string $tableName, array $info): array
    {
        $this->assertService->verify(isset($info['name']), 'No trigger name specified');
        $this->assertService->verify(isset($info['time']), 'No trigger time specified');
        $this->assertService->verify(isset($info['event']), 'No trigger event specified');
        $this->assertService->verify(isset($info['action']), 'No trigger action specified');

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
     * @param array<string> $info
     *
     * @return array{query: string, params: array<string>}
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
     * @param array<string> $info
     *
     * @return array{query: string, params: array<string>}
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
     * @param array<string, null|string> $values
     *
     * @return array{query: string, params: array<string>}
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
     * @param array<string> $params
     *
     * @return array{query: string, params: array<string>}
     */
    private function rawQuery(string $query, array $params): array
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
     * @return array{query: string, params: array<string>}
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
     * @return array{query: string, params: array<string>}
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
