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

final class CreateTriggerActionHandler extends AbstractActionHandler
{
    public function getType(): string
    {
        return 'create_trigger';
    }

    public function buildStep(array $action): MigrationStep
    {
        $tableName = $this->requireTableName($action);

        if (!isset($action['trigger']) || !is_array($action['trigger']))
        {
            throw new \InvalidArgumentException('No trigger array specified');
        }

        $trigger = $action['trigger'];

        if (!isset($trigger['name']))
        {
            throw new \InvalidArgumentException('No trigger name specified');
        }

        if (!isset($trigger['time']))
        {
            throw new \InvalidArgumentException('No trigger time specified');
        }

        if (!isset($trigger['event']))
        {
            throw new \InvalidArgumentException('No trigger event specified');
        }

        if (!isset($trigger['action']))
        {
            throw new \InvalidArgumentException('No trigger action specified');
        }

        $query = <<<SQL
CREATE TRIGGER `{$trigger['name']}` {$trigger['time']} {$trigger['event']} ON `{$tableName}`
    FOR EACH ROW {$trigger['action']}
SQL;

        return new QueryStep($query, []);
    }
}
