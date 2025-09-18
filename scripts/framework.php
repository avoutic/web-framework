#!/usr/bin/env php
<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use WebFramework\Core\ConsoleApplication;
use WebFramework\Task\TaskRunner;

// Get the project root directory
$projectRoot = __DIR__;

if (!file_exists($projectRoot.'/vendor/autoload.php'))
{
    echo 'Composer not initialized'.PHP_EOL;
    echo 'Please run "composer install"'.PHP_EOL;
    echo PHP_EOL;
    echo 'This script is intended to be copied as "framework" to the root of your project'.PHP_EOL;
    echo 'Do not run it from the scripts directory'.PHP_EOL;

    exit(1);
}

require_once $projectRoot.'/vendor/autoload.php';

$taskRunner = new TaskRunner($projectRoot);
$taskRunner->setPlaintext();
$taskRunner->build();

/** @var ConsoleApplication $application */
$application = $taskRunner->get(ConsoleApplication::class);

exit($application->run($argv));
