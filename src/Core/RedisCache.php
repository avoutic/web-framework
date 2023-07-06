<?php

namespace WebFramework\Core;

use Cache\Adapter\Redis\RedisCachePool;

class RedisCache implements Cache
{
    private RedisCachePool $pool;

    public function __construct(RedisCachePool $pool)
    {
        $this->pool = $pool;
    }

    public function exists(string $path): bool
    {
        return $this->pool->hasItem($path);
    }

    public function get(string $path): mixed
    {
        $item = $this->pool->getItem($path);

        if (!$item->isHit())
        {
            return false;
        }

        return $item->get();
    }

    public function set(string $path, mixed $obj, ?int $expiresAfter = null): void
    {
        $item = $this->pool->getItem($path);
        $item->set($obj);
        $item->expiresAfter($expiresAfter);
        $this->pool->save($item);
    }

    /**
     * @param array<string> $tags
     */
    public function setWithTags(string $path, mixed $obj, array $tags, ?int $expiresAfter = null): void
    {
        $item = $this->pool->getItem($path);
        $item->set($obj)->setTags($tags);
        $item->expiresAfter($expiresAfter);
        $this->pool->save($item);
    }

    public function invalidate(string $path): void
    {
        $this->pool->deleteItem($path);
    }

    /**
     * @param array<string> $tags
     */
    public function invalidateTags(array $tags): void
    {
        $this->pool->invalidateTags($tags);
    }

    public function flush(): void
    {
        $this->pool->clear();
    }
}
