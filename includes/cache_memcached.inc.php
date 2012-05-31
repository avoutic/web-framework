<?php
class CacheMemcached
{
    function _construct()
    {
        die("IMPLEMENTATION_MISSING");
    }
};

$core_factory->register('cache', 'memcached', 'CacheMemcached');
?>
