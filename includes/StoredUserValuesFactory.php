<?php

namespace WebFramework\Core;

class StoredUserValuesFactory
{
    /** @var array<string, StoredUserValues> */
    private array $cache = [];

    public function __construct(
        private Database $database,
    ) {
    }

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
