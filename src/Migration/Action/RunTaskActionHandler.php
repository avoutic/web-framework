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

use Psr\Container\ContainerInterface as Container;
use WebFramework\Migration\MigrationStep;
use WebFramework\Migration\TaskStep;
use WebFramework\Task\Task;

final class RunTaskActionHandler implements ActionHandler
{
    public function __construct(private Container $container) {}

    public function getType(): string
    {
        return 'run_task';
    }

    public function buildStep(array $action): MigrationStep
    {
        if (!isset($action['task']) || !is_string($action['task']))
        {
            throw new \InvalidArgumentException('No task specified');
        }

        $task = $this->container->get($action['task']);
        if (!$task instanceof Task)
        {
            throw new \RuntimeException("Task {$action['task']} does not implement Task");
        }

        return new TaskStep($task);
    }
}
