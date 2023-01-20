<?php

// Global configuration
//
if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

use WebFramework\Core\DatabaseManager;
use WebFramework\Core\WF;

$framework = new WF();

header('content-type: text/plain');

try
{
    // Initialize WF
    //
    $framework->skip_db_check();
    $framework->init();
    $app_dir = $framework->get_app_dir();

    $db_manager = new DatabaseManager();

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
    WF::verify(is_array($change_set), 'No change set array found');

    $db_manager->execute($change_set, true);
}
catch (Throwable $e)
{
    echo('Unhandled exception');

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
