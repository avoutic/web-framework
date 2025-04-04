<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Task;

use WebFramework\Core\TaskInterface;
use WebFramework\Core\TaskRunner;

class TaskRunnerTask implements TaskInterface
{
    public function __construct(
        private readonly TaskRunner $taskRunner,
        private readonly string $taskClass
    ) {}

    public function execute(): void
    {
        if (!class_exists($this->taskClass))
        {
            throw new \RuntimeException("Task class '{$this->taskClass}' does not exist");
        }

        $task = $this->taskRunner->get($this->taskClass);
        if (!$task instanceof TaskInterface)
        {
            throw new \RuntimeException("Task {$this->taskClass} does not implement TaskInterface");
        }

        $task->execute();
    }
}
