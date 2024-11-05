<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\SanityCheck;

/**
 * Abstract class Base.
 *
 * Provides a base implementation for sanity check modules.
 */
abstract class Base implements SanityCheckModule
{
    /** @var bool Whether fixing is allowed */
    protected bool $allowFixing = false;

    /** @var bool Whether verbose output is enabled */
    protected bool $verbose = false;

    /** @var array<mixed> Configuration for the sanity check */
    protected array $config = [];

    /**
     * Perform the sanity checks.
     *
     * @return bool True if all checks pass, false otherwise
     */
    abstract public function performChecks(): bool;

    /**
     * Set the configuration for the sanity check.
     *
     * @param array<mixed> $config The configuration array
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Allow fixing of issues during the sanity check.
     */
    public function allowFixing(): void
    {
        $this->allowFixing = true;
    }

    /**
     * Enable verbose output during the sanity check.
     */
    public function setVerbose(): void
    {
        $this->verbose = true;
    }

    /**
     * Add output if verbose mode is enabled.
     *
     * @param string $str The string to output
     */
    protected function addOutput(string $str): void
    {
        if ($this->verbose)
        {
            echo $str;
        }
    }
}
