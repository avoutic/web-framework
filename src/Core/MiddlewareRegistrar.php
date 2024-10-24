<?php

namespace WebFramework\Core;

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
     * @param App $app The Slim application instance
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
