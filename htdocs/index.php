<?php

// Global configuration
//
if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

use Psr\Container\ContainerInterface as Container;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use WebFramework\Core\BootstrapService;
use WebFramework\Core\ConfigBuilder;
use WebFramework\Core\DebugService;
use WebFramework\Core\Instrumentation;
use WebFramework\Core\MiddlewareRegistrar;
use WebFramework\Core\ReportFunction;
use WebFramework\Core\RouteRegistrar;

// Build config
//
$appDir = __DIR__.'/..';
$configs = [
    '/config/base_config.php',
    '/config/config.php',
    '?/config/config_local.php',
];

$configBuilder = new ConfigBuilder($appDir);
$config = $configBuilder->buildConfig(
    $configs,
);

// Build container
//
$builder = new DI\ContainerBuilder();
$builder->addDefinitions(['config_tree' => $config]);
$builder->addDefinitions($configBuilder->getFlattenedConfig());

foreach ($config['definition_files'] as $file)
{
    $builder->addDefinitions("{$appDir}/definitions/{$file}");
}

$container = $builder->build();

try
{
    // Create and start Slim framework
    //
    AppFactory::setContainer($container);
    $app = AppFactory::create();

    // Start instrumentation transaction
    //
    $instrumentation = $container->get(Instrumentation::class);
    $request = ServerRequestFactory::createFromGlobals();
    $transaction = $instrumentation->startTransaction('http.server', $request->getUri()->getPath());

    // Bootstrap WebFramework
    //
    $span = $instrumentation->startSpan('app.bootstrap');
    $bootstrapService = $container->get(BootstrapService::class);
    $bootstrapService->bootstrap();
    $instrumentation->finishSpan($span);

    // Registrer Post Routing Middlewares
    //
    $middlewareRegistrar = new MiddlewareRegistrar($app);
    $middlewareRegistrar->register($config['middlewares']['post_routing']);

    // Registrer Routes
    //
    $routeRegistrar = new RouteRegistrar($app, $container, $appDir);
    $routeRegistrar->register($config['route_files']);
    $app->addRoutingMiddleware();

    // Registrer Pre Routing Middlewares
    //
    $middlewareRegistrar->register($config['middlewares']['pre_routing']);

    // Handle the request
    //
    $span = $instrumentation->startSpan('app.run');
    $app->run();
    $instrumentation->finishSpan($span);

    $instrumentation->finishTransaction($transaction);
}
catch (Throwable $e)
{
    $request = ServerRequestFactory::createFromGlobals();

    $debugService = $container->get(DebugService::class);
    $errorReport = $debugService->getThrowableReport($e, $request);

    $reportFunction = $container->get(ReportFunction::class);
    $reportFunction->report($e->getMessage(), 'unhandled_exception', $errorReport);

    $message = ($container->get('debug')) ? $errorReport['message'] : $errorReport['low_info_message'];

    header('Content-type: text/plain');
    echo $message;
}
