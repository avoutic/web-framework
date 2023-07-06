<?php

namespace WebFramework\Core;

class NullCache implements Cache
{
    public function exists(string $path): bool
    {
        return false;
    }

    public function get(string $path): mixed
    {
        return false;
    }

    public function set(string $path, mixed $obj, ?int $expiresAfter = null): void
    {
    }

    /**
     * @param array<string> $tags
     */
    public function setWithTags(string $path, mixed $obj, array $tags, ?int $expiresAfter = null): void
    {
    }

    public function invalidate(string $path): void
    {
    }

    /**
     * @param array<string> $tags
     */
    public function invalidateTags(array $tags): void
    {
    }

    public function flush(): void
    {
    }
}
