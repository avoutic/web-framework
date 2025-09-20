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

final class RenameTableActionHandler extends AbstractActionHandler
{
    public function getType(): string
    {
        return 'rename_table';
    }

    public function buildStep(array $action): MigrationStep
    {
        $tableName = $this->requireTableName($action);

        if (!isset($action['new_name']))
        {
            throw new \InvalidArgumentException('No new_name specified');
        }

        $query = <<<SQL
ALTER TABLE `{$tableName}`
    RENAME TO `{$action['new_name']}`
SQL;

        return new QueryStep($query, []);
    }
}
