<?php

namespace WebFramework\Core;

use Slim\App;

class MiddlewareRegistrar
{
    public function __construct(
        private App $app,
    ) {
    }

    /**
     * @param array<string> $middlewares
     */
    public function register(array $middlewares): void
    {
        foreach ($middlewares as $middleware)
        {
            $this->app->add($middleware);
        }
    }
}
