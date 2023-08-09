<?php

namespace WebFramework\Core;

class NullCache implements CacheInterface
{
    public function __construct(array $config)
    {
    }

    public function exists(string $path): bool
    {
        return false;
    }

    public function get(string $path): mixed
    {
        return false;
    }

    public function set(string $path, mixed $obj, ?int $expires_after = null): void
    {
    }

    /**
     * @param array<string> $tags
     */
    public function set_with_tags(string $path, mixed $obj, array $tags, ?int $expires_after = null): void
    {
    }

    public function invalidate(string $path): void
    {
    }

    /**
     * @param array<string> $tags
     */
    public function invalidate_tags(array $tags): void
    {
    }

    public function flush(): void
    {
    }
}
