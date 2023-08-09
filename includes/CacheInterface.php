<?php

namespace WebFramework\Core;

interface CacheInterface
{
    /**
     * @param array<mixed> $config
     */
    public function __construct(array $config);

    public function exists(string $path): bool;

    public function get(string $path): mixed;

    public function set(string $path, mixed $obj, ?int $expires_after = null): void;

    /**
     * @param array<string> $tags
     */
    public function set_with_tags(string $path, mixed $obj, array $tags, ?int $expires_after = null): void;

    public function invalidate(string $path): void;

    /**
     * @param array<string> $tags
     */
    public function invalidate_tags(array $tags): void;

    public function flush(): void;
}
