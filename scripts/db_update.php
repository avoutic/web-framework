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
    $framework->skip_app_db_version_check();
    $framework->init();
    $app_dir = $framework->get_app_dir();

    $db_manager = new DatabaseManager();

    $current_version = $db_manager->get_current_version();
    $required_version = WF::get_config('versions.required_app_db');

    if ($required_version <= $current_version)
    {
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
        WF::verify(is_array($change_set), 'No change set array found');

        $db_manager->execute($change_set);
        $current_version = $db_manager->get_current_version();
        $next_version = $current_version + 1;
    }
}
catch (Throwable $e)
{
    echo('Unhandled exception');

    if ($framework->get_config('debug') == true)
    {
        echo($e->getMessage().PHP_EOL);
        print_r($e->getTrace());
    }

    WF::report_error($e->getMessage(), $e->getTrace());

    exit();
}
