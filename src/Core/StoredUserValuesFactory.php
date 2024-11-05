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

/**
 * Class StoredUserValuesFactory.
 *
 * Factory for creating StoredUserValues instances.
 */
class StoredUserValuesFactory
{
    /** @var array<string, StoredUserValues> Cache of StoredUserValues instances */
    private array $cache = [];

    /**
     * StoredUserValuesFactory constructor.
     *
     * @param Database $database The database interface
     */
    public function __construct(
        private Database $database,
    ) {}

    /**
     * Get a StoredUserValues instance for a specific user and module.
     *
     * @param int    $userId The ID of the user
     * @param string $module The module name
     *
     * @return StoredUserValues The StoredUserValues instance
     */
    public function get(int $userId, string $module): StoredUserValues
    {
        $cacheId = "{$userId}_{$module}";

        if (!isset($this->cache[$cacheId]))
        {
            $this->cache[$cacheId] = new StoredUserValues($this->database, $userId, $module);
        }

        return $this->cache[$cacheId];
    }
}
