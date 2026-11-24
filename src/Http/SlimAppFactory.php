<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Http;

use Psr\Container\ContainerInterface as Container;
use Slim\App;
use Slim\Factory\AppFactory;
use WebFramework\Config\ConfigService;

/**
 * Class SlimAppFactory.
 *
 * Creates the Slim application with the configured middlewares and routes, without running
 * it. SlimAppTask uses it to serve a request. Tests and other PSR-15 clients can use it to
 * get the same application and pass requests to App::handle().
 *
 * The application is created on the container this factory is resolved from. The factory
 * does not bootstrap the framework: call BootstrapService::bootstrap() once per process first.
 */
class SlimAppFactory
{
    /**
     * SlimAppFactory constructor.
     *
     * @param ConfigService $configService The configuration service
     * @param Container     $container     The dependency injection container
     */
    public function __construct(
        private ConfigService $configService,
        private Container $container,
    ) {}

    /**
     * Create a Slim application with the configured middlewares and routes.
     *
     * @return App<Container> The Slim application, ready to run or to handle requests
     *
     * @uses config middlewares.post_routing
     * @uses config routes
     * @uses config middlewares.pre_routing
     */
    public function create(): App
    {
        $app = AppFactory::create(container: $this->container);

        // The order matters: a middleware that is added later wraps the ones added before
        // it. So the post routing middlewares run inside the routing middleware, and the
        // pre routing middlewares outside of it
        //
        $middlewareRegistrar = new MiddlewareRegistrar($app);
        $middlewareRegistrar->register($this->configService->get('middlewares.post_routing'));

        $routeRegistrar = new RouteRegistrar($app, $this->container);
        $routeRegistrar->register($this->configService->get('routes'));
        $app->addRoutingMiddleware();

        $middlewareRegistrar->register($this->configService->get('middlewares.pre_routing'));

        return $app;
    }
}
