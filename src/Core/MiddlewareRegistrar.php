<?php

/**
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
 * Class MiddlewareRegistrar.
 *
 * Registers middleware with the Slim application.
 */
class MiddlewareRegistrar
{
    /**
     * MiddlewareRegistrar constructor.
     *
     * @param App<Container> $app The Slim application instance
     */
    public function __construct(
        private App $app,
    ) {}

    /**
     * Register an array of middleware with the application.
     *
     * @param array<string> $middlewares An array of middleware class names to register
     */
    public function register(array $middlewares): void
    {
        foreach ($middlewares as $middleware)
        {
            $this->app->add($middleware);
        }
    }
}
