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
use Slim\App;

/**
 * Class RouteRegistrar.
 *
 * Responsible for registering routes from multiple RouteSet instances.
 */
class RouteRegistrar
{
    /**
     * RouteRegistrar constructor.
     *
     * @param App<Container> $app       The Slim application instance
     * @param Container      $container The dependency injection container
     */
    public function __construct(
        private App $app,
        private Container $container,
    ) {}

    /**
     * Register routes from multiple RouteSet instances.
     *
     * @param array<string> $routeSets An array of fully qualified class names of RouteSet implementations
     */
    public function register(array $routeSets): void
    {
        foreach ($routeSets as $class)
        {
            $routeSet = $this->container->get($class);
            $routeSet->register($this->app);
        }
    }
}
