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

final class RawQueryActionHandler implements ActionHandler
{
    public function getType(): string
    {
        return 'raw_query';
    }

    public function buildStep(array $action): MigrationStep
    {
        if (!isset($action['query']) || !is_string($action['query']))
        {
            throw new \InvalidArgumentException('No query specified');
        }

        if (!isset($action['params']) || !is_array($action['params']))
        {
            throw new \InvalidArgumentException('No params array specified');
        }

        return new QueryStep($action['query'], $action['params']);
    }
}
