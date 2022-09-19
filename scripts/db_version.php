<?php
// Global configuration
//
if (!file_exists(__DIR__ . '/../vendor/autoload.php'))
    die('Composer not initialized');
require_once(__DIR__ . '/../vendor/autoload.php');

use WebFramework\Core\WF;
use WebFramework\Core\DatabaseManager;

$framework = new WF();

try
{
    // Initialize WF
    //
    $framework->skip_app_db_version_check();
    $framework->init();

    $db_manager = new DatabaseManager();

    $current_version = $db_manager->get_current_version();

    // Verify database scheme hash
    //
    var_dump($db_manager->verify_hash());
}
catch (Throwable $e)
{
    print('Unhandled exception');

    if ($framework->get_config('debug') == true)
    {
        print($e->getMessage().PHP_EOL);
        print_r($e->getTrace());
    }

    WF::report_error($e->getMessage(), $e->getTrace());
    exit();
}
?>
