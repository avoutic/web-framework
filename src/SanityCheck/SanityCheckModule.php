<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\SanityCheck;

/**
 * Interface SanityCheckModule.
 *
 * Defines the contract for sanity check modules in the WebFramework.
 */
interface SanityCheckModule
{
    /**
     * Set the configuration for the sanity check module.
     *
     * @param array<mixed> $config The configuration array
     */
    public function setConfig(array $config): void;

    /**
     * Allow fixing of issues during the sanity check.
     */
    public function allowFixing(): void;

    /**
     * Perform the sanity checks.
     *
     * @return bool True if all checks pass, false otherwise
     */
    public function performChecks(): bool;

    /**
     * Enable verbose output during the sanity check.
     */
    public function setVerbose(): void;
}
