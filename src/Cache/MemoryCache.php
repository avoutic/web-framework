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

use Carbon\Carbon;

/**
 * Class MemoryCache.
 *
 * An implementation of the Cache interface that keeps its items in memory.
 *
 * The items live as long as this object, so within one process. Use it in tests, and in
 * tools that run as one process. In a web application every request starts with an empty
 * cache, so it does not cache anything between requests.
 */
class MemoryCache implements Cache
{
    /** @var array<string, array{value: mixed, expires_at: null|int, tags: array<string>}> */
    private array $items = [];

    public function exists(string $path): bool
    {
        return $this->getItem($path) !== null;
    }

    public function get(string $path): mixed
    {
        $item = $this->getItem($path);

        return ($item === null) ? false : $item['value'];
    }

    public function set(string $path, mixed $obj, ?int $expiresAfter = null): void
    {
        $this->setWithTags($path, $obj, [], $expiresAfter);
    }

    public function setWithTags(string $path, mixed $obj, array $tags, ?int $expiresAfter = null): void
    {
        $this->items[$path] = [
            'value' => $obj,
            'expires_at' => ($expiresAfter === null) ? null : Carbon::now()->getTimestamp() + $expiresAfter,
            'tags' => $tags,
        ];
    }

    public function invalidate(string $path): void
    {
        unset($this->items[$path]);
    }

    public function invalidateTags(array $tags): void
    {
        foreach ($this->items as $path => $item)
        {
            if (count(array_intersect($item['tags'], $tags)))
            {
                unset($this->items[$path]);
            }
        }
    }

    public function flush(): void
    {
        $this->items = [];
    }

    /**
     * Get an item that is present and has not expired.
     *
     * @return null|array{value: mixed, expires_at: null|int, tags: array<string>}
     */
    private function getItem(string $path): ?array
    {
        if (!isset($this->items[$path]))
        {
            return null;
        }

        $item = $this->items[$path];

        if ($item['expires_at'] !== null && $item['expires_at'] <= Carbon::now()->getTimestamp())
        {
            unset($this->items[$path]);

            return null;
        }

        return $item;
    }
}
