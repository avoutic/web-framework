<?php

// Global configuration
//
if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

use Slim\Psr7\Factory\ServerRequestFactory;
use WebFramework\Core\ConfigBuilder;
use WebFramework\Core\DebugService;
use WebFramework\Core\ReportFunction;
use WebFramework\Core\WF;

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
$builder->addDefinitions("{$app_dir}/includes/di_definitions.php");
$container = $builder->build();

// Create and start framework
//
$core_framework = new WF($container);
$container->set('framework', $core_framework);
$framework = null;

try
{
    // Initialize WF
    //
    $core_framework->init();

    // Allow app to check data and config sanity
    //
    $core_framework->check_sanity();

    // Load routes
    //
    $framework = $container->get(WebFramework\Core\WFWebHandler::class);

    if (is_file("{$app_dir}/includes/site_logic.inc.php"))
    {
        include_once "{$app_dir}/includes/site_logic.inc.php";
    }

    $framework->handle_request();
}
catch (Throwable $e)
{
    $request = ServerRequestFactory::createFromGlobals();

    $debug_service = $container->get(DebugService::class);
    $error_report = $debug_service->get_throwable_report($e, $request);

    $report_function = $container->get(ReportFunction::class);
    $report_function->report($e->getMessage(), 'unhandled_exception', $error_report);

    $message = ($container->get('debug')) ? $error_report['message'] : $error_report['low_info_message'];

    if ($framework !== null)
    {
        $message = "<pre>{$message}</pre>";
        $framework->exit_send_error(500, 'Unhandled exception', 'generic', $message);
    }
    else
    {
        header('Content-type: text/plain');
        echo $message;
    }
}
