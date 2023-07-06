<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use Slim\App;

class RouteRegistrar
{
    public function __construct(
        private App $app,
        private Container $container,
        private string $appDir,
    ) {
    }

    /**
     * @param array<string> $routeFiles
     */
    public function register(array $routeFiles): void
    {
        foreach ($routeFiles as $file)
        {
            if (!file_exists("{$this->appDir}/routes/{$file}"))
            {
                throw new \InvalidArgumentException("The route file \"routes/{$file}\" does not exist");
            }

            $app = $this->app;
            $container = $this->container;

            include_once "{$this->appDir}/routes/{$file}";
        }
    }
}
