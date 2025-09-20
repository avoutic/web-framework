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

final class AddConstraintActionHandler extends AbstractActionHandler
{
    public function getType(): string
    {
        return 'add_constraint';
    }

    public function buildStep(array $action): MigrationStep
    {
        $tableName = $this->requireTableName($action);

        if (!isset($action['constraint']) || !is_array($action['constraint']))
        {
            throw new \InvalidArgumentException('No constraint array specified');
        }

        $fieldLines = [];
        $constraintLines = [];

        $this->appendConstraintStatements($tableName, $action['constraint'], $constraintLines);

        $lines = array_merge($fieldLines, $constraintLines);
        $linesFmt = implode(",\n    ADD ", $lines);

        $query = <<<SQL
ALTER TABLE `{$tableName}`
    ADD {$linesFmt}
SQL;

        return new QueryStep($query, []);
    }
}
