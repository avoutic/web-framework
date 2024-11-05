<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;

/**
 * Class ContainerWrapper.
 *
 * A wrapper for the dependency injection container.
 * This class is only for compatibility reasons and should not be used in new code.
 */
class ContainerWrapper
{
    /** @var Container The wrapped container instance */
    private static Container $container;

    /**
     * Set the container instance.
     *
     * @param Container $container The container to wrap
     */
    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    /**
     * Get the wrapped container instance.
     *
     * @return Container The wrapped container
     */
    public static function get(): Container
    {
        return self::$container;
    }
}
