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

final class AddForeignKeyActionHandler extends AbstractActionHandler
{
    public function getType(): string
    {
        return 'add_foreign_key';
    }

    public function buildStep(array $action): MigrationStep
    {
        $tableName = $this->requireTableName($action);

        if (!isset($action['column']) || !is_string($action['column']))
        {
            throw new \InvalidArgumentException('No column specified');
        }

        if (!isset($action['foreign_table']) || !is_string($action['foreign_table']))
        {
            throw new \InvalidArgumentException('No foreign_table specified');
        }

        if (!isset($action['foreign_field']) || !is_string($action['foreign_field']))
        {
            throw new \InvalidArgumentException('No foreign_field specified');
        }

        $constraintName = $action['constraint_name'] ?? "foreign_{$tableName}_{$action['column']}";
        $onDelete = isset($action['on_delete']) ? 'ON DELETE '.$action['on_delete'] : '';
        $onUpdate = isset($action['on_update']) ? 'ON UPDATE '.$action['on_update'] : '';

        $query = sprintf(
            "ALTER TABLE `%s`\n    ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) %s %s",
            $tableName,
            $constraintName,
            $action['column'],
            $action['foreign_table'],
            $action['foreign_field'],
            $onDelete,
            $onUpdate
        );

        return new QueryStep(trim($query), []);
    }
}
