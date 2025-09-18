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
 * Value object representing a command line option for a task.
 */
class TaskOption
{
    /**
     * @param callable(string):void|callable(void):void $setter callback that applies the option value or toggles the option
     */
    public function __construct(
        private string $long,
        private ?string $short,
        private string $description,
        private bool $hasValue,
        private $setter,
    ) {}

    public function getLong(): string
    {
        return $this->long;
    }

    public function getShort(): ?string
    {
        return $this->short;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function hasValue(): bool
    {
        return $this->hasValue;
    }

    /**
     * Apply a value-bearing option.
     */
    public function applyValue(string $value): void
    {
        if (!$this->hasValue)
        {
            throw new \BadMethodCallException('Option does not support a value');
        }

        ($this->setter)($value);
    }

    /**
     * Trigger a flag-style option.
     */
    public function trigger(): void
    {
        if ($this->hasValue)
        {
            throw new \BadMethodCallException('Option requires a value');
        }

        // @phpstan-ignore-next-line
        ($this->setter)();
    }

    /**
     * Human readable representation for usage output.
     */
    public function getDisplayName(): string
    {
        if ($this->short)
        {
            return sprintf('--%s | -%s', $this->long, $this->short);
        }

        return sprintf('--%s', $this->long);
    }
}
