<?php

namespace WebFramework\Core;

interface Cache
{
    public function exists(string $path): bool;

    public function get(string $path): mixed;

    public function set(string $path, mixed $obj, ?int $expiresAfter = null): void;

    /**
     * @param array<string> $tags
     */
    public function setWithTags(string $path, mixed $obj, array $tags, ?int $expiresAfter = null): void;

    public function invalidate(string $path): void;

    /**
     * @param array<string> $tags
     */
    public function invalidateTags(array $tags): void;

    public function flush(): void;
}
