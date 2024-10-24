<?php

namespace WebFramework\Core;

use Cache\Adapter\Redis\RedisCachePool;

/**
 * Class RedisCache.
 *
 * A Redis-based implementation of the Cache interface.
 */
class RedisCache implements Cache
{
    /**
     * RedisCache constructor.
     *
     * @param Instrumentation $instrumentation The instrumentation service for performance tracking
     * @param RedisCachePool  $pool            The Redis cache pool
     */
    public function __construct(
        private Instrumentation $instrumentation,
        private RedisCachePool $pool,
    ) {
        $this->pool = $pool;
    }

    /**
     * Check if an item exists in the cache.
     *
     * @param string $path The cache key
     *
     * @return bool True if the item exists, false otherwise
     */
    public function exists(string $path): bool
    {
        $span = $this->instrumentation->startSpan('cache.exists');
        $result = $this->pool->hasItem($path);
        $this->instrumentation->finishSpan($span);

        return $result;
    }

    /**
     * Retrieve an item from the cache.
     *
     * @param string $path The cache key
     *
     * @return mixed The cached value or false if not found
     */
    public function get(string $path): mixed
    {
        $span = $this->instrumentation->startSpan('cache.get');
        $item = $this->pool->getItem($path);
        $this->instrumentation->finishSpan($span);

        if (!$item->isHit())
        {
            return false;
        }

        return $item->get();
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
        $span = $this->instrumentation->startSpan('cache.set');
        $item = $this->pool->getItem($path);
        $item->set($obj);
        $item->expiresAfter($expiresAfter);
        $this->pool->save($item);
        $this->instrumentation->finishSpan($span);
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
        $span = $this->instrumentation->startSpan('cache.set_with_tags');
        $item = $this->pool->getItem($path);
        $item->set($obj)->setTags($tags);
        $item->expiresAfter($expiresAfter);
        $this->pool->save($item);
        $this->instrumentation->finishSpan($span);
    }

    /**
     * Invalidate (remove) an item from the cache.
     *
     * @param string $path The cache key to invalidate
     */
    public function invalidate(string $path): void
    {
        $span = $this->instrumentation->startSpan('cache.invalidate');
        $this->pool->deleteItem($path);
        $this->instrumentation->finishSpan($span);
    }

    /**
     * Invalidate all items associated with the given tags.
     *
     * @param array<string> $tags An array of tags to invalidate
     */
    public function invalidateTags(array $tags): void
    {
        $span = $this->instrumentation->startSpan('cache.invalidate_with_tags');
        $this->pool->invalidateTags($tags);
        $this->instrumentation->finishSpan($span);
    }

    /**
     * Flush the entire cache.
     */
    public function flush(): void
    {
        $this->pool->clear();
    }
}
