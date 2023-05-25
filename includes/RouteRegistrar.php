<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use Slim\App;

class RouteRegistrar
{
    public function __construct(
        private App $app,
        private Container $container,
        private string $app_dir,
    ) {
    }

    /**
     * @param array<string> $route_files
     */
    public function register(array $route_files): void
    {
        foreach ($route_files as $file)
        {
            if (!file_exists("{$this->app_dir}/routes/{$file}"))
            {
                throw new \InvalidArgumentException("The route file \"routes/{$file}\" does not exist");
            }

            $app = $this->app;
            $container = $this->container;

            include_once "{$this->app_dir}/routes/{$file}";
        }
    }
}
