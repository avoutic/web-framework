<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;

// Only for compatibility reason
// Do not use in new code
class ContainerWrapper
{
    protected static Container $container;

    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    public static function get(): Container
    {
        return self::$container;
    }
}
