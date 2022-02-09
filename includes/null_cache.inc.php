<?php
class NullCache extends FrameworkCore
{
    function __construct()
    {
        parent::__construct();
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
