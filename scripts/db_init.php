<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use WebFramework\Core\DebugService;
use WebFramework\Core\TaskRunner;
use WebFramework\Task\DbInitTask;

if (!file_exists(__DIR__.'/../vendor/autoload.php'))
{
    exit('Composer not initialized');
}

require_once __DIR__.'/../vendor/autoload.php';

header('content-type: text/plain');

$taskRunner = new TaskRunner(__DIR__.'/..');
$taskRunner->build();

try
{
    $taskRunner->execute(DbInitTask::class);
}
catch (Throwable $e)
{
    echo PHP_EOL.PHP_EOL;

    if (!$taskRunner->get('debug'))
    {
        echo 'Unhandled exception'.PHP_EOL;

        exit();
    }

    $debugService = $taskRunner->get(DebugService::class);
    $errorReport = $debugService->getThrowableReport($e);

    echo $errorReport['message'];
}
