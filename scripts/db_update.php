<?php
// Global configuration
//
if (!file_exists(__DIR__ . '/../../vendor/autoload.php'))
    die('Composer not initialized');
require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ .'/../includes/wf_core.inc.php');
require_once(__DIR__ .'/../includes/db_manager.inc.php');

// Initialize WF
//
$framework = new WF();
$framework->skip_app_db_version_check();
$framework->init();

$db_manager = new DBManager();

$current_version = $db_manager->get_current_version();
$required_version =  WF::get_config('versions.required_app_db');

if ($required_version <= $current_version)
    exit();

// Retrieve relevant change set
//
$next_version = $current_version + 1;

while ($next_version <= $required_version)
{
    $version_file = __DIR__ . "/../../db_scheme/{$next_version}.inc.php";

    if (!file_exists($version_file))
    {
        echo " - No changeset for {$next_version} available".PHP_EOL;
        exit();
    }

    $change_set = require($version_file);
    WF::verify(is_array($change_set), 'No change set array found');

    $db_manager->execute($change_set);
    $current_version = $db_manager->get_current_version();
    $next_version = $current_version + 1;
}
?>
