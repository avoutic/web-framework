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

use WebFramework\Core\ConsoleTask;
use WebFramework\Core\TaskInterface;
use WebFramework\Core\TaskRunner;

class TaskRunnerTask extends ConsoleTask
{
    private ?string $taskClass = null;

    public function __construct(
        private readonly TaskRunner $taskRunner,
    ) {}

    public function getCommand(): string
    {
        return 'task:run';
    }

    public function getDescription(): string
    {
        return 'Run a task';
    }

    public function getArguments(): array
    {
        return [
            [
                'name' => 'taskClass',
                'description' => 'The fully qualified class name of the task to run',
                'required' => true,
                'setter' => [$this, 'setTaskClass'],
            ],
        ];
    }

    public function setTaskClass(string $taskClass): void
    {
        $this->taskClass = $taskClass;
    }

    public function execute(): void
    {
        if ($this->taskClass === null)
        {
            throw new \RuntimeException('Task class not set');
        }

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
