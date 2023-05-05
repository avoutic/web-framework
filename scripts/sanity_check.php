<?php

// Global configuration
//
if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

use WebFramework\Core\WF;

// Initialize WF
//
$framework = new WF();

try
{
    // Initialize WF
    //
    $framework->init();

    header('content-type: text/plain');

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
    echo('Unhandled exception'.PHP_EOL);

    if ($framework->get_config('debug') == true)
    {
        echo($e->getMessage().PHP_EOL);
        print_r($e->getTrace());
    }

    WF::report_error($e->getMessage(), $e->getTrace());

    exit();
}
