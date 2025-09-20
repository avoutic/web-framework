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

final class AddColumnActionHandler extends AbstractActionHandler
{
    public function getType(): string
    {
        return 'add_column';
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
        $linesFmt = implode(",\n    ADD ", $lines);

        $query = <<<SQL
ALTER TABLE `{$tableName}`
    ADD {$linesFmt}
SQL;

        return new QueryStep($query, []);
    }
}
