<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Migration;

use WebFramework\Database\Database;
use WebFramework\Task\Task;

/**
 * Wraps a Task so it can be executed as part of a migration.
 *
 * @codeCoverageIgnore
 */
final class TaskStep implements MigrationStep
{
    public function __construct(private Task $task) {}

    public function getTask(): Task
    {
        return $this->task;
    }

    public function describe(): string
    {
        return 'Task: '.get_class($this->task);
    }

    public function execute(Database $database): void
    {
        // Database dependency is unused for task execution.
        unset($database);
        $this->task->execute();
    }
}
