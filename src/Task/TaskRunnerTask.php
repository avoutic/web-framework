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

use Carbon\Carbon;

class TaskRunnerTask extends ConsoleTask
{
    private ?string $taskClass = null;
    private bool $isContinuous = false;
    private int $delayBetweenRunsInSecs = 1;
    private ?int $maxRuntimeInSecs = null;

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

    public function getUsage(): string
    {
        return <<<EOF
        Usage:
          {$this->getCommand()} [options] <taskClass>

        The task class should be a fully qualified class name and must implement the
        Task interface.

        Examples:
          {$this->getCommand()} --continuous --delay 60 --max-runtime 3600 App\\Task\\MyTask
          {$this->getCommand()} App\\Task\\MyTask

        Options:
          --continuous      Run the task continuously
          --delay <secs>    The delay between continuous runs in seconds
          --max-runtime <secs> The maximum runtime in seconds
        EOF;
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

    public function getOptions(): array
    {
        return [
            [
                'long' => 'continuous',
                'description' => 'Run the task continuously',
                'has_value' => false,
                'setter' => [$this, 'setContinuous'],
            ],
            [
                'long' => 'delay',
                'description' => 'The delay between continuous runs in seconds',
                'has_value' => true,
                'setter' => [$this, 'setDelayBetweenRuns'],
            ],
            [
                'long' => 'max-runtime',
                'description' => 'The maximum runtime in seconds',
                'has_value' => true,
                'setter' => [$this, 'setMaxRunTime'],
            ],
        ];
    }

    public function setTaskClass(string $taskClass): void
    {
        $this->taskClass = $taskClass;
    }

    /**
     * Set the task to run continuously.
     */
    public function setContinuous(): void
    {
        $this->isContinuous = true;
    }

    /**
     * Set the delay between continuous runs.
     *
     * @param int $secs The delay in seconds
     */
    public function setDelayBetweenRuns(int $secs): void
    {
        $this->delayBetweenRunsInSecs = $secs;
    }

    /**
     * Set the maximum runtime for continuous execution.
     *
     * @param int $secs The maximum runtime in seconds
     */
    public function setMaxRunTime(int $secs): void
    {
        $this->maxRuntimeInSecs = $secs;
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
        if (!$task instanceof Task)
        {
            throw new \RuntimeException("Task {$this->taskClass} does not implement Task");
        }

        if ($this->isContinuous)
        {
            $start = Carbon::now();

            while (true)
            {
                $task->execute();

                if ($this->maxRuntimeInSecs)
                {
                    if ($start->diffInSeconds() > $this->maxRuntimeInSecs)
                    {
                        break;
                    }
                }

                Carbon::sleep($this->delayBetweenRunsInSecs);
            }
        }
        else
        {
            $task->execute();
        }
    }
}
