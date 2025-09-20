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

use WebFramework\Core\Database;

/**
 * Represents a single executable unit in a migration.
 */
interface MigrationStep
{
    /**
     * Human readable description used for dry-runs and logging.
     */
    public function describe(): string;

    /**
     * Execute the step using the provided database connection.
     */
    public function execute(Database $database): void;
}
