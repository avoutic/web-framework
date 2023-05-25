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
use WebFramework\Core\MiddlewareRegistrar;
use WebFramework\Core\ReportFunction;
use WebFramework\Core\RouteRegistrar;

// Build config
//
$app_dir = __DIR__.'/..';
$configs = [
    '/config/base_config.php',
    '/config/config.php',
    '?/config/config_local.php',
];

$config_builder = new ConfigBuilder($app_dir);
$config = $config_builder->build_config(
    $configs,
);

// Build container
//
$builder = new DI\ContainerBuilder();
$builder->addDefinitions(['config_tree' => $config]);
$builder->addDefinitions($config_builder->get_flattened_config());

foreach ($config['definition_files'] as $file)
{
    $builder->addDefinitions("{$app_dir}/definitions/{$file}");
}

$container = $builder->build();

try
{
    // Create and start Slim framework
    //
    AppFactory::setContainer($container);
    $app = AppFactory::create();

    // Bootstrap WebFramework
    //
    $bootstrap_service = $container->get(BootstrapService::class);
    $bootstrap_service->bootstrap();

    // Registrer Middlewares
    //
    $middleware_registrar = new MiddlewareRegistrar($app);
    $middleware_registrar->register($config['middlewares'], $config['debug']);

    // Registrer Routes
    //
    $route_registrar = new RouteRegistrar($app, $container, $app_dir);
    $route_registrar->register($config['route_files']);

    // Handle the request
    //
    $app->run();
}
catch (Throwable $e)
{
    $request = ServerRequestFactory::createFromGlobals();

    $debug_service = $container->get(DebugService::class);
    $error_report = $debug_service->get_throwable_report($e, $request);

    $report_function = $container->get(ReportFunction::class);
    $report_function->report($e->getMessage(), 'unhandled_exception', $error_report);

    $message = ($container->get('debug')) ? $error_report['message'] : $error_report['low_info_message'];

    header('Content-type: text/plain');
    echo $message;
}
