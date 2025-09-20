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

use WebFramework\Migration\MigrationStep;
use WebFramework\Migration\QueryStep;

final class CreateTableActionHandler extends AbstractActionHandler
{
    public function getType(): string
    {
        return 'create_table';
    }

    public function buildStep(array $action): MigrationStep
    {
        $tableName = $this->requireTableName($action);

        if (!isset($action['fields']) || !is_array($action['fields']))
        {
            throw new \InvalidArgumentException('No fields array specified');
        }

        if (!isset($action['constraints']) || !is_array($action['constraints']))
        {
            throw new \InvalidArgumentException('No constraints array specified');
        }

        $fieldLines = ['`id` int(11) NOT NULL AUTO_INCREMENT'];
        $constraintLines = ['PRIMARY KEY (`id`)'];

        foreach ($action['fields'] as $info)
        {
            $this->appendFieldStatements($tableName, $info, $fieldLines, $constraintLines);
        }

        foreach ($action['constraints'] as $info)
        {
            $this->appendConstraintStatements($tableName, $info, $constraintLines);
        }

        $lines = array_merge($fieldLines, $constraintLines);
        $linesFmt = implode(",\n    ", $lines);

        $query = <<<SQL
CREATE TABLE `{$tableName}` (
    {$linesFmt}
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;

        return new QueryStep($query, []);
    }
}
