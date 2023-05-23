<?php

// Global configuration
//
if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

use WebFramework\Core\ConfigBuilder;
use WebFramework\Core\DatabaseManager;
use WebFramework\Core\DebugService;
use WebFramework\Core\ReportFunction;
use WebFramework\Core\WF;

header('content-type: text/plain');

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
$config_builder->populate_internals('app', 'app');

// Build container
//
$builder = new DI\ContainerBuilder();
$builder->addDefinitions(['config_tree' => $config_builder->get_config()]);
$builder->addDefinitions($config_builder->get_flattened_config());
$builder->addDefinitions("{$app_dir}/vendor/avoutic/web-framework/includes/di_definitions.php");
$builder->addDefinitions("{$app_dir}/includes/di_definitions.php");
$container = $builder->build();

// Create and start framework
//
$framework = new WF($container);
$container->set('framework', $framework);

try
{
    // Initialize WF
    //
    $framework->skip_db_check();
    $framework->init();

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
    $debug_service = $container->get(DebugService::class);
    $error_report = $debug_service->get_throwable_report($e);

    $report_function = $container->get(ReportFunction::class);
    $report_function->report($e->getMessage(), 'unhandled_exception', $error_report);

    if ($container->get('debug') == true)
    {
        echo $error_report['message'];
    }
    else
    {
        echo $error_report['low_info_message'];
    }
}
