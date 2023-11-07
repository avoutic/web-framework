<?php

namespace WebFramework\Core;

use Slim\App;

interface RouteSet
{
    public function register(App $app): void;
}
