<?php

namespace WebFramework\Core;

use Slim\App;

/**
 * Interface RouteSet.
 *
 * Defines the contract for classes that register routes with a Slim application.
 */
interface RouteSet
{
    /**
     * Register routes with the given Slim application instance.
     *
     * @param App $app The Slim application instance
     */
    public function register(App $app): void;
}
