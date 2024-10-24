<?php

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

class SlimAppTask implements TaskInterface
{
    public function __construct(
        private Container $container,
        private ConfigService $configService,
        private Instrumentation $instrumentation,
        private BootstrapService $bootstrapService,
    ) {}

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
