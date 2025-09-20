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

final class InsertRowActionHandler extends AbstractActionHandler
{
    public function getType(): string
    {
        return 'insert_row';
    }

    public function buildStep(array $action): MigrationStep
    {
        $tableName = $this->requireTableName($action);

        if (!isset($action['values']) || !is_array($action['values']))
        {
            throw new \InvalidArgumentException('No values array specified');
        }

        $fieldsFmt = '';
        $params = [];
        $first = true;

        foreach ($action['values'] as $key => $value)
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

        return new QueryStep($query, $params);
    }
}
