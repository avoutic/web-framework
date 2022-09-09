<?php
require_once(WF::$includes.'cache_interface.inc.php');

use Cache\Adapter\Redis\RedisCachePool;

class RedisCache implements CacheInterface
{
    private $client;
    private $pool;

    function __construct($config)
    {
        WF::verify(isset($config['hostname']), 'No hostname set');
        WF::verify(isset($config['port']), 'No port set');

        $client = new Redis();
        $client->pconnect($config['hostname'], $config['port']);
        $this->pool = new RedisCachePool($client);
    }

    function exists($path)
    {
        return $this->pool->hasItem($path);
    }

    function get($path)
    {
        $item = $this->pool->getItem($path);

        if (!$item->isHit())
            return false;

        return $item->get();
    }

    function set($path, $obj, $expires_after = null)
    {
        $item = $this->pool->getItem($path);
        $item->set($obj);
        $item->expiresAfter($expires_after);
        $this->pool->save($item);
    }

    function set_with_tags($path, $obj, $tags, $expires_after = null)
    {
        $item = $this->pool->getItem($path);
        $item->set($obj)->setTags($tags);
        $item->expiresAfter($expires_after);
        $this->pool->save($item);
    }

    function invalidate($path)
    {
        $this->pool->deleteItem($path);
    }

    function invalidate_tags($tags)
    {
        $this->pool->invalidateTags($tags);
    }

    function flush()
    {
        $this->pool->clear();
    }
};
?>
