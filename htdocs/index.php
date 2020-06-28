<?php
// Global configuration
//
if (!file_exists(__DIR__ . '/../../vendor/autoload.php'))
    die('Composer not initialized');
require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ .'/../includes/wf_web_handler.inc.php');

// Initialize WF
//
$framework = new WFWebHandler();
$framework->init();

// Load route and hooks array and site specific logic if available
//
if (is_file(WF::$site_includes."site_logic.inc.php"))
    include_once(WF::$site_includes."site_logic.inc.php");

$framework->handle_request();
?>
