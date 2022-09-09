<?php
require_once(WF::$includes.'cache_interface.inc.php');

class NullCache implements CacheInterface
{
    function __construct(array $config)
    {
    }

    function exists($path)
    {
        return false;
    }

    function get($path)
    {
        return false;
    }

    function set($path, $obj, $expires_after = null)
    {
    }

    function invalidate($path)
    {
    }

    function flush()
    {
    }
};
?>
