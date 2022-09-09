<?php
require_once(WF::$includes.'cache_interface.inc.php');

class NullCache implements CacheInterface
{
    function __construct(array $config)
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

    public function invalidate(string $path): void
    {
    }

    public function flush(): void
    {
    }
};
?>
