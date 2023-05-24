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
use WebFramework\Core\ConfigBuilder;
use WebFramework\Core\ContainerWrapper;

// Build config
//
$app_dir = __DIR__.'/..';
$configs = [
    '/vendor/avoutic/web-framework/includes/BaseConfig.php',
    '/includes/config.php',
    '?/includes/config_local.php',
];

$config_builder = new ConfigBuilder($app_dir);
$config_builder->build_config(
    $configs,
);
$config_builder->populate_internals($_SERVER['SERVER_NAME'] ?? '', $_SERVER['SERVER_NAME'] ?? '');

// Build container
//
$builder = new DI\ContainerBuilder();
$builder->addDefinitions(['config_tree' => $config_builder->get_config()]);
$builder->addDefinitions($config_builder->get_flattened_config());
$builder->addDefinitions("{$app_dir}/vendor/avoutic/web-framework/includes/di_definitions.php");

// Add any app specific definitions
//
// $builder->addDefinitions("{$app_dir}/includes/di_definitions.php");

$container = $builder->build();

// If you have old-style code you probably need ContainerWrapper
//
require_once "{$app_dir}/vendor/avoutic/web-framework/includes/ContainerWrapper.php";

require_once "{$app_dir}/vendor/avoutic/web-framework/includes/defines.inc.php";

ContainerWrapper::setContainer($container);

if ($container->get('preload'))
{
    require_once "{$app_dir}/includes/preload.inc.php";
}

// Create and start framework
//
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add global Middleware
//

// Error Middleware should always be added last
//
$errorMiddleware = $app->addErrorMiddleware(true, false, false);

include_once "{$app_dir}/includes/routes/generic.php";

$app->run();
