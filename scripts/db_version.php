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
    $framework->skip_app_db_version_check();
    $framework->skip_wf_db_version_check();
    $framework->init();

    $db_manager = $container->get(DatabaseManager::class);

    $current_version = $db_manager->get_current_version();

    // Verify database scheme hash
    //
    print_r($db_manager->verify_hash());
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
