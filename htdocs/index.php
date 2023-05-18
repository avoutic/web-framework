<?php

// Global configuration
//
if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

use Slim\Psr7\Factory\ServerRequestFactory;
use WebFramework\Core\WF;

$core_framework = new WF();
$framework = null;

try
{
    // Initialize WF
    //
    $core_framework->init();

    // Allow app to check data and config sanity
    //
    $core_framework->check_sanity();

    // Load route and hooks array and site specific logic if available
    //
    $app_dir = $core_framework->get_app_dir();

    $framework = $core_framework->get_web_handler();

    if (is_file("{$app_dir}/includes/site_logic.inc.php"))
    {
        include_once "{$app_dir}/includes/site_logic.inc.php";
    }

    $framework->handle_request();
}
catch (Throwable $e)
{
    $message = 'Final catch block';
    $request = ServerRequestFactory::createFromGlobals();

    $debug_service = $core_framework->get_debug_service();
    $error_report = $debug_service->get_error_report($e->getTrace(), $request, 'unhandled_exception', $e->getMessage());

    $report_function = $core_framework->get_report_function();
    $report_function->report($e->getMessage(), 'unhandled_exception', $error_report);

    $title = 'Unhandled exception';
    $message = "<pre>{$error_report['message']}</pre>";

    if ($framework !== null)
    {
        $framework->exit_send_error(500, $title, 'generic', $message);
    }
    else
    {
        echo "{$title} {$message}";

        exit();
    }
}
