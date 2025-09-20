<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Migration\Action;

abstract class AbstractActionHandler implements ActionHandler
{
    /**
     * @param array<string, mixed> $action
     */
    protected function requireTableName(array $action): string
    {
        if (!isset($action['table_name']) || !is_string($action['table_name']))
        {
            throw new \InvalidArgumentException('No table_name specified');
        }

        return $action['table_name'];
    }

    /**
     * @param array<string, mixed> $info
     * @param array<string>        $fieldLines
     * @param array<string>        $constraintLines
     */
    protected function appendFieldStatements(string $tableName, array $info, array &$fieldLines, array &$constraintLines): void
    {
        if (!isset($info['type']))
        {
            throw new \InvalidArgumentException('No field type specified');
        }

        if (!isset($info['name']))
        {
            throw new \InvalidArgumentException('No field name specified');
        }

        $dbType = strtoupper((string) $info['type']);
        $null = (isset($info['null']) && $info['null']);

        if ($info['type'] === 'foreign_key')
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
        elseif (in_array($info['type'], ['varchar', 'char', 'binary', 'varbinary'], true))
        {
            if (!isset($info['size']))
            {
                throw new \InvalidArgumentException("No {$info['type']} size set");
            }

            $dbType = strtoupper((string) $info['type'])."({$info['size']})";
        }
        elseif (in_array($info['type'], ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'numeric'], true))
        {
            $size = isset($info['size']) ? "({$info['size']})" : '';
            $dbType = strtoupper((string) $info['type']).$size;
        }

        $nullFmt = $null ? 'NULL' : 'NOT NULL';
        $defaultFmt = '';
        $afterFmt = isset($info['after']) ? "AFTER {$info['after']}" : '';

        if (array_key_exists('default', $info))
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
                ], true);

                $defaultFmt = $needsQuotes
                    ? "DEFAULT '{$info['default']}'"
                    : "DEFAULT {$info['default']}";
            }
        }

        $fieldLines[] = trim("`{$info['name']}` {$dbType} {$nullFmt} {$defaultFmt} {$afterFmt}");

        if ($info['type'] === 'foreign_key')
        {
            $constraintLines[] = "KEY `foreign_{$tableName}_{$info['name']}` (`{$info['name']}`)";
            $onDelete = isset($info['on_delete']) ? "ON DELETE {$info['on_delete']}" : '';
            $onUpdate = isset($info['on_update']) ? "ON UPDATE {$info['on_update']}" : '';

            $constraintLines[] = trim(
                "CONSTRAINT `foreign_{$tableName}_{$info['name']}` FOREIGN KEY (`{$info['name']}`) "
                ."REFERENCES `{$info['foreign_table']}` (`{$info['foreign_field']}`) {$onDelete} {$onUpdate}"
            );
        }
    }

    /**
     * @param array<string, mixed> $info
     * @param array<string>        $constraintLines
     */
    protected function appendConstraintStatements(string $tableName, array $info, array &$constraintLines): void
    {
        if (!isset($info['type']))
        {
            throw new \InvalidArgumentException('No constraint type specified');
        }

        if ($info['type'] === 'unique')
        {
            if (!isset($info['values']) || !is_array($info['values']))
            {
                throw new \InvalidArgumentException('Values for unique constraint must be an array');
            }

            $valuesFmt = implode('_', $info['values']);
            $fieldsFmt = implode('`, `', $info['values']);

            $constraintLines[] = "UNIQUE KEY `unique_{$tableName}_{$valuesFmt}` (`{$fieldsFmt}`)";
        }
        elseif ($info['type'] === 'index')
        {
            if (!isset($info['name']))
            {
                throw new \InvalidArgumentException('No name for index specified');
            }

            if (!isset($info['values']) || !is_array($info['values']))
            {
                throw new \InvalidArgumentException('Values for index constraint must be an array');
            }

            $fieldsFmt = implode('`, `', $info['values']);

            $constraintLines[] = "INDEX `{$info['name']}` (`{$fieldsFmt}`)";
        }
        else
        {
            throw new \RuntimeException("Unknown constraint type '{$info['type']}'");
        }
    }
}
