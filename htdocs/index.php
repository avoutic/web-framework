<?php

// Global configuration
//
if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

use WebFramework\Core\WF;
use WebFramework\Core\WFWebHandler;

$framework = new WFWebHandler();

try
{
    // Initialize WF
    //
    $framework->init();

    // Allow app to check data and config sanity
    //
    $framework->check_sanity();

    // Load route and hooks array and site specific logic if available
    //
    $app_dir = $framework->get_app_dir();
    if (is_file("{$app_dir}/includes/site_logic.inc.php"))
    {
        include_once "{$app_dir}/includes/site_logic.inc.php";
    }

    $framework->handle_request();
}
catch (Throwable $e)
{
    echo('Unhandled exception'.PHP_EOL);

    if ($framework->get_config('debug') == true)
    {
        echo($e->getMessage().PHP_EOL);
        print_r($e->getTrace());
    }

    if (!$e instanceof WebFramework\Core\VerifyException)
    {
        WF::report_error($e->getMessage(), $e->getTrace());
    }

    exit();
}
