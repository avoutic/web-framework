<?php

// Global configuration
//
if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

use WebFramework\Core\ConfigBuilder;
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
    $framework->init();

    $class_names = $framework->get_sanity_checks_to_run();

    foreach ($class_names as $class_name => $module_config)
    {
        $sanity_check = $framework->instantiate_sanity_check($class_name, $module_config);
        $sanity_check->allow_fixing();
        $sanity_check->set_verbose();
        $sanity_check->perform_checks();
    }
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
