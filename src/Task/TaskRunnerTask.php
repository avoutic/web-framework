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
use WebFramework\Exception\ArgumentParserException;

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
            new TaskArgument('taskClass', 'The fully qualified class name of the task to run', true, [$this, 'setTaskClass']),
        ];
    }

    public function getOptions(): array
    {
        return [
            new TaskOption('continuous', null, 'Run the task continuously', false, [$this, 'setContinuous']),
            new TaskOption('delay', null, 'The delay between continuous runs in seconds', true, [$this, 'setDelayBetweenRuns']),
            new TaskOption('max-runtime', null, 'The maximum runtime in seconds', true, [$this, 'setMaxRunTime']),
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
     * @param string $secs The delay in seconds
     */
    public function setDelayBetweenRuns(string $secs): void
    {
        if (!is_numeric($secs))
        {
            throw new ArgumentParserException('Delay between runs must be a number');
        }

        $this->delayBetweenRunsInSecs = (int) $secs;
    }

    /**
     * Set the maximum runtime for continuous execution.
     *
     * @param string $secs The maximum runtime in seconds
     */
    public function setMaxRunTime(string $secs): void
    {
        if (!is_numeric($secs))
        {
            throw new ArgumentParserException('Max runtime must be a number');
        }

        $this->maxRuntimeInSecs = (int) $secs;
    }

    public function execute(): void
    {
        if ($this->taskClass === null)
        {
            throw new ArgumentParserException('Task class not set');
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
