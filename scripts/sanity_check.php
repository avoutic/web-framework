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
$config = $config_builder->build_config(
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

    $bootstrap_service->set_sanity_check_fixing();
    $bootstrap_service->set_sanity_check_verbose();
    $bootstrap_service->set_sanity_check_force_run();

    $bootstrap_service->bootstrap();
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

    echo $error_report['low_info_message'];
}
