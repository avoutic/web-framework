<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

abstract class ConsoleTask implements TaskInterface
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
     * @return array<array{name: string, description: string, required: bool, setter: callable}> The arguments for the task
     */
    public function getArguments(): array
    {
        return [];
    }

    /**
     * Get the options for the task.
     *
     * @return array<array{long: string, short?: string, description: string, has_value: bool, setter: callable}> The options for the task
     */
    public function getOptions(): array
    {
        return [];
    }
}
