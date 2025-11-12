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

use WebFramework\Database\Database;

/**
 * Represents an SQL query that should be executed during a migration.
 *
 * @codeCoverageIgnore
 */
final class QueryStep implements MigrationStep
{
    /**
     * @param string        $query  The SQL query to execute
     * @param array<string> $params The parameters for the prepared query
     */
    public function __construct(
        private string $query,
        private array $params = []
    ) {}

    /**
     * Return the SQL query for display.
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Return the SQL parameters.
     *
     * @return array<string>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function describe(): string
    {
        return $this->query;
    }

    public function execute(Database $database): void
    {
        $database->query($this->query, $this->params);
    }
}
