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

abstract class ConsoleTask implements Task
{
    abstract public function getCommand(): string;

    public function getDescription(): string
    {
        return '';
    }

    public function getUsage(): string
    {
        return '';
    }

    /**
     * Get the arguments for the task.
     *
     * @return array<TaskArgument>
     */
    public function getArguments(): array
    {
        return [];
    }

    /**
     * Get the options for the task.
     *
     * @return array<TaskOption>
     */
    public function getOptions(): array
    {
        return [];
    }

    /**
     * Check if the task handles its own bootstrapping.
     *
     * @return bool True if the task handles its own bootstrapping, false otherwise
     */
    public function handlesOwnBootstrapping(): bool
    {
        return false;
    }
}
