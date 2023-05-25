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
$config = $config_builder->build_config(
    $configs,
);

// Build container
//
$builder = new DI\ContainerBuilder();
$builder->addDefinitions(['config_tree' => $config_builder->get_config()]);
$builder->addDefinitions($config_builder->get_flattened_config());

foreach ($config['definition_files'] as $file)
{
    $builder->addDefinitions("{$app_dir}/definitions/{$file}");
}

$container = $builder->build();

try
{
    $bootstrap_service = $container->get(BootstrapService::class);

    $bootstrap_service->skip_sanity_checks();

    $bootstrap_service->bootstrap();

    $db_manager = $container->get(DatabaseManager::class);

    $current_version = $db_manager->get_current_version();
    $required_version = $container->get('versions.required_app_db');

    if ($required_version <= $current_version)
    {
        echo 'Nothing to be done'.PHP_EOL;

        exit();
    }

    // Retrieve relevant change set
    //
    $next_version = $current_version + 1;

    while ($next_version <= $required_version)
    {
        $version_file = "{$app_dir}/db_scheme/{$next_version}.inc.php";

        if (!file_exists($version_file))
        {
            echo " - No changeset for {$next_version} available".PHP_EOL;

            exit();
        }

        $change_set = require $version_file;
        if (!is_array($change_set))
        {
            throw new \RuntimeException('No change set array found');
        }

        $db_manager->execute($change_set);
        $current_version = $db_manager->get_current_version();
        $next_version = $current_version + 1;
    }
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
