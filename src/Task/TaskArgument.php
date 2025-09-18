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

/**
 * Value object representing a command line argument for a task.
 */
class TaskArgument
{
    /**
     * @param callable(string):void $setter callback that applies the argument value
     */
    public function __construct(
        private string $name,
        private string $description,
        private bool $required,
        private $setter,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Apply the argument value using the configured setter.
     */
    public function apply(string $value): void
    {
        ($this->setter)($value);
    }
}
