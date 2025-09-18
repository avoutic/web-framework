<?php

use WebFramework\Core\HttpApplication;
use WebFramework\Task\TaskRunner;

if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

$taskRunner = new TaskRunner(__DIR__.'/..');
$taskRunner->build();

/** @var HttpApplication $application */
$application = $taskRunner->get(HttpApplication::class);
$application->run();
