<?php

namespace WebFramework\Core;

use Cache\Adapter\Redis\RedisCachePool;

class RedisCache implements Cache
{
    public function __construct(
        private Instrumentation $instrumentation,
        private RedisCachePool $pool,
    ) {
        $this->pool = $pool;
    }

    public function exists(string $path): bool
    {
        $span = $this->instrumentation->startSpan('cache.exists');
        $result = $this->pool->hasItem($path);
        $this->instrumentation->finishSpan($span);

        return $result;
    }

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
     * @param array<string> $tags
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

    public function invalidate(string $path): void
    {
        $span = $this->instrumentation->startSpan('cache.invalidate');
        $this->pool->deleteItem($path);
        $this->instrumentation->finishSpan($span);
    }

    /**
     * @param array<string> $tags
     */
    public function invalidateTags(array $tags): void
    {
        $span = $this->instrumentation->startSpan('cache.invalidate_with_tags');
        $this->pool->invalidateTags($tags);
        $this->instrumentation->finishSpan($span);
    }

    public function flush(): void
    {
        $this->pool->clear();
    }
}
