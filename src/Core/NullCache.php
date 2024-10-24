<?php

namespace WebFramework\Core;

/**
 * Class NullCache.
 *
 * A null implementation of the Cache interface that performs no caching operations.
 * Useful for testing or when caching is disabled.
 */
class NullCache implements Cache
{
    /**
     * Check if an item exists in the cache.
     *
     * @param string $path The cache key
     *
     * @return bool Always returns false
     */
    public function exists(string $path): bool
    {
        return false;
    }

    /**
     * Retrieve an item from the cache.
     *
     * @param string $path The cache key
     *
     * @return mixed Always returns false
     */
    public function get(string $path): mixed
    {
        return false;
    }

    /**
     * Store an item in the cache.
     *
     * @param string   $path         The cache key
     * @param mixed    $obj          The value to store
     * @param null|int $expiresAfter The number of seconds after which the item expires
     */
    public function set(string $path, mixed $obj, ?int $expiresAfter = null): void
    {
        // No operation
    }

    /**
     * Store an item in the cache with associated tags.
     *
     * @param string        $path         The cache key
     * @param mixed         $obj          The value to store
     * @param array<string> $tags         An array of tags to associate with the item
     * @param null|int      $expiresAfter The number of seconds after which the item expires
     */
    public function setWithTags(string $path, mixed $obj, array $tags, ?int $expiresAfter = null): void
    {
        // No operation
    }

    /**
     * Invalidate (remove) an item from the cache.
     *
     * @param string $path The cache key to invalidate
     */
    public function invalidate(string $path): void
    {
        // No operation
    }

    /**
     * Invalidate all items associated with the given tags.
     *
     * @param array<string> $tags An array of tags to invalidate
     */
    public function invalidateTags(array $tags): void
    {
        // No operation
    }

    /**
     * Flush the entire cache.
     */
    public function flush(): void
    {
        // No operation
    }
}
