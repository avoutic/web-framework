<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Task;

use Psr\Container\ContainerInterface as Container;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use WebFramework\Core\BootstrapService;
use WebFramework\Core\ConfigService;
use WebFramework\Core\Instrumentation;
use WebFramework\Core\MiddlewareRegistrar;
use WebFramework\Core\RouteRegistrar;
use WebFramework\Core\TaskInterface;

/**
 * Class SlimAppTask.
 *
 * This task is responsible for setting up and running the Slim application.
 */
class SlimAppTask implements TaskInterface
{
    /**
     * SlimAppTask constructor.
     *
     * @param Container        $container        The dependency injection container
     * @param ConfigService    $configService    The configuration service
     * @param Instrumentation  $instrumentation  The instrumentation service
     * @param BootstrapService $bootstrapService The bootstrap service
     */
    public function __construct(
        private Container $container,
        private ConfigService $configService,
        private Instrumentation $instrumentation,
        private BootstrapService $bootstrapService,
    ) {}

    /**
     * Execute the Slim application task.
     *
     * This method sets up the Slim application, registers middlewares and routes,
     * and handles the request.
     *
     * @uses config middlewares.post_routing
     * @uses config routes
     * @uses config middlewares.pre_routing
     */
    public function execute(): void
    {
        // Create and start Slim framework
        //
        AppFactory::setContainer($this->container);
        $app = AppFactory::create();

        // Start instrumentation transaction
        //
        $request = ServerRequestFactory::createFromGlobals();
        $transaction = $this->instrumentation->startTransaction('http.server', $request->getUri()->getPath());

        // Bootstrap WebFramework
        //
        $span = $this->instrumentation->startSpan('app.bootstrap');
        $this->bootstrapService->bootstrap();
        $this->instrumentation->finishSpan($span);

        // Registrer Post Routing Middlewares
        //
        $middlewareRegistrar = new MiddlewareRegistrar($app);
        $middlewareRegistrar->register($this->configService->get('middlewares.post_routing'));

        // Registrer Routes
        //
        $routeRegistrar = new RouteRegistrar($app, $this->container);
        $routeRegistrar->register($this->configService->get('routes'));
        $app->addRoutingMiddleware();

        // Registrer Pre Routing Middlewares
        //
        $middlewareRegistrar->register($this->configService->get('middlewares.pre_routing'));

        // Handle the request
        //
        $span = $this->instrumentation->startSpan('app.run');
        $app->run();
        $this->instrumentation->finishSpan($span);

        $this->instrumentation->finishTransaction($transaction);
    }
}
