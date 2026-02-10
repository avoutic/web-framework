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

final class DropForeignKeyActionHandler extends AbstractActionHandler
{
    public function getType(): string
    {
        return 'drop_foreign_key';
    }

    public function buildStep(array $action): MigrationStep
    {
        $tableName = $this->requireTableName($action);

        if (!isset($action['constraint_name']) || !is_string($action['constraint_name']))
        {
            throw new \InvalidArgumentException('No constraint_name specified');
        }

        $query = sprintf(
            "ALTER TABLE `%s`\n    DROP FOREIGN KEY `%s`",
            $tableName,
            $action['constraint_name']
        );

        return new QueryStep($query, []);
    }
}
