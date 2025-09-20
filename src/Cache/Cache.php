<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Cache;

/**
 * Interface Cache.
 *
 * Defines the contract for cache implementations in the WebFramework.
 */
interface Cache
{
    /**
     * Check if an item exists in the cache.
     *
     * @param string $path The cache key
     *
     * @return bool True if the item exists, false otherwise
     */
    public function exists(string $path): bool;

    /**
     * Retrieve an item from the cache.
     *
     * @param string $path The cache key
     *
     * @return mixed The cached value or false if not found
     */
    public function get(string $path): mixed;

    /**
     * Store an item in the cache.
     *
     * @param string   $path         The cache key
     * @param mixed    $obj          The value to store
     * @param null|int $expiresAfter The number of seconds after which the item expires (null for no expiration)
     */
    public function set(string $path, mixed $obj, ?int $expiresAfter = null): void;

    /**
     * Store an item in the cache with associated tags.
     *
     * @param string        $path         The cache key
     * @param mixed         $obj          The value to store
     * @param array<string> $tags         An array of tags to associate with the item
     * @param null|int      $expiresAfter The number of seconds after which the item expires (null for no expiration)
     */
    public function setWithTags(string $path, mixed $obj, array $tags, ?int $expiresAfter = null): void;

    /**
     * Invalidate (remove) an item from the cache.
     *
     * @param string $path The cache key to invalidate
     */
    public function invalidate(string $path): void;

    /**
     * Invalidate all items associated with the given tags.
     *
     * @param array<string> $tags An array of tags to invalidate
     */
    public function invalidateTags(array $tags): void;

    /**
     * Flush the entire cache.
     */
    public function flush(): void;
}
