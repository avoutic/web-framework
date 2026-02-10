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

final class ModifyForeignKeyActionHandler extends AbstractActionHandler
{
    public function getType(): string
    {
        return 'modify_foreign_key';
    }

    /**
     * @return array<MigrationStep>
     */
    public function buildStep(array $action): array
    {
        $tableName = $this->requireTableName($action);

        if (!isset($action['constraint_name']) || !is_string($action['constraint_name']))
        {
            throw new \InvalidArgumentException('No constraint_name specified');
        }

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

        $constraintName = $action['constraint_name'];
        $onDelete = isset($action['on_delete']) ? 'ON DELETE '.$action['on_delete'] : '';
        $onUpdate = isset($action['on_update']) ? 'ON UPDATE '.$action['on_update'] : '';

        $dropQuery = sprintf(
            "ALTER TABLE `%s`\n    DROP FOREIGN KEY `%s`",
            $tableName,
            $constraintName
        );

        $addQuery = sprintf(
            "ALTER TABLE `%s`\n    ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) %s %s",
            $tableName,
            $constraintName,
            $action['column'],
            $action['foreign_table'],
            $action['foreign_field'],
            $onDelete,
            $onUpdate
        );

        return [
            new QueryStep($dropQuery, []),
            new QueryStep(trim($addQuery), []),
        ];
    }
}
