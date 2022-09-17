<?php
namespace WebFramework\Core;

use Redis;
use Cache\Adapter\Redis\RedisCachePool;

class RedisCache implements CacheInterface
{
    private RedisCachePool $pool;

    /**
     * @param array<string> $config
     */
    function __construct(array $config)
    {
        WF::verify(isset($config['hostname']), 'No hostname set');
        WF::verify(isset($config['port']), 'No port set');
        WF::verify(isset($config['password']), 'No password set');

        $client = new Redis();
        $result = $client->pconnect(
            $config['hostname'],
            (int) $config['port'],
            1,
            "wf",
            0,
            0,
            ['auth' => $config['password']]
        );
        WF::verify($result === true, 'Failed to connect to Redis cache');

        $this->pool = new RedisCachePool($client);

        try {
            // Workaround: Without trying to check something, the connection is not yet verified.
            //
            $this->pool->hasItem('errors');
        }
        catch (\Throwable $e)
        {
            WF::verify(false, 'Failed to connect to Redis cache');
        }
    }

    public function exists(string $path): bool
    {
        return $this->pool->hasItem($path);
    }

    public function get(string $path): mixed
    {
        $item = $this->pool->getItem($path);

        if (!$item->isHit())
            return false;

        return $item->get();
    }

    public function set(string $path, mixed $obj, ?int $expires_after = null): void
    {
        $item = $this->pool->getItem($path);
        $item->set($obj);
        $item->expiresAfter($expires_after);
        $this->pool->save($item);
    }

    /**
     * @param array<string> $tags
     */
    public function set_with_tags(string $path, mixed $obj, array $tags, ?int $expires_after = null): void
    {
        $item = $this->pool->getItem($path);
        $item->set($obj)->setTags($tags);
        $item->expiresAfter($expires_after);
        $this->pool->save($item);
    }

    public function invalidate(string $path): void
    {
        $this->pool->deleteItem($path);
    }

    /**
     * @param array<string> $tags
     */
    public function invalidate_tags(array $tags): void
    {
        $this->pool->invalidateTags($tags);
    }

    public function flush(): void
    {
        $this->pool->clear();
    }
};
?>
