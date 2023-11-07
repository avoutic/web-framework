<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use Slim\App;

class RouteRegistrar
{
    public function __construct(
        private App $app,
        private Container $container,
    ) {
    }

    /**
     * @param array<string> $routeSets
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
