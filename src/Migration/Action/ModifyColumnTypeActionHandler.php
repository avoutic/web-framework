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

final class ModifyColumnTypeActionHandler extends AbstractActionHandler
{
    public function getType(): string
    {
        return 'modify_column_type';
    }

    public function buildStep(array $action): MigrationStep
    {
        $tableName = $this->requireTableName($action);

        if (!isset($action['field']) || !is_array($action['field']))
        {
            throw new \InvalidArgumentException('No field array specified');
        }

        $fieldLines = [];
        $constraintLines = [];

        $this->appendFieldStatements($tableName, $action['field'], $fieldLines, $constraintLines);

        $lines = array_merge($fieldLines, $constraintLines);
        $linesFmt = implode(",\n    MODIFY ", $lines);

        $query = <<<SQL
ALTER TABLE `{$tableName}`
    MODIFY {$linesFmt}
SQL;

        return new QueryStep($query, []);
    }
}
