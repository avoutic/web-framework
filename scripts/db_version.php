<?php
// Global configuration
//
if (!file_exists(__DIR__ . '/../../vendor/autoload.php'))
    die('Composer not initialized');
require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ .'/../includes/wf_core.inc.php');
require_once(__DIR__ .'/../includes/db_manager.inc.php');

try
{
    // Initialize WF
    //
    $framework = new WF();
    $framework->skip_app_db_version_check();
    $framework->init();

    $db_manager = new DBManager();

    $current_version = $db_manager->get_current_version();

    // Verify database scheme hash
    //
    var_dump($db_manager->verify_hash());
}
catch (Throwable $e)
{
    print('Unhandled exception');
    WF::report_error($e->getMessage(), $e->getTrace());
    exit();
}
?>
