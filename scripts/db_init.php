<?php

// Global configuration
//
if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

use WebFramework\Core\BootstrapService;
use WebFramework\Core\ConfigBuilder;
use WebFramework\Core\DatabaseManager;
use WebFramework\Core\DebugService;

header('content-type: text/plain');

// Build config
//
$app_dir = __DIR__.'/..';
$configs = [
    '/config/base_config.php',
    '/config/config.php',
    '?/config/config_local.php',
];

$config_builder = new ConfigBuilder($app_dir);
$config_builder->build_config(
    $configs,
);

// Build container
//
$builder = new DI\ContainerBuilder();
$builder->addDefinitions(['config_tree' => $config_builder->get_config()]);
$builder->addDefinitions($config_builder->get_flattened_config());

$definition_files = glob("{$app_dir}/definitions/*.php") ?: [];
foreach ($definition_files as $file)
{
    $builder->addDefinitions($file);
}

$container = $builder->build();

try
{
    $bootstrap_service = $container->get(BootstrapService::class);

    $bootstrap_service->skip_sanity_checks();

    $bootstrap_service->bootstrap();

    $db_manager = $container->get(DatabaseManager::class);

    if ($db_manager->is_initialized())
    {
        echo ' - Already initialized. Exiting.'.PHP_EOL;

        exit();
    }

    $scheme_file = "{$app_dir}/vendor/avoutic/web-framework/bootstrap/scheme_v1.inc.php";

    if (!file_exists($scheme_file))
    {
        echo " - Scheme file {$scheme_file} not found".PHP_EOL;

        exit();
    }

    $change_set = require $scheme_file;
    if (!is_array($change_set))
    {
        throw new \RuntimeException('No change set array found');
    }

    $db_manager->execute($change_set, true);
}
catch (Throwable $e)
{
    echo PHP_EOL.PHP_EOL;

    if (!$container->get('debug'))
    {
        echo 'Unhandled exception'.PHP_EOL;

        exit();
    }

    $debug_service = $container->get(DebugService::class);
    $error_report = $debug_service->get_throwable_report($e);

    echo $error_report['message'];
}
