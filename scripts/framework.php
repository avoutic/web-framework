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

use WebFramework\Core\TaskRunner;
use WebFramework\Task\TaskRunnerTask;

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

// Get command from command line
$scriptName = basename($argv[0]);
$command = $argv[1] ?? null;
if (!$command || $command === 'help')
{
    echo 'Usage: '.$scriptName.' <command> [options]'.PHP_EOL.PHP_EOL;
    echo 'Available commands:'.PHP_EOL;
    echo '  help         Show this help'.PHP_EOL;
    echo PHP_EOL;
    echo '  db:init      Initialize the database'.PHP_EOL;
    echo '  db:update    Update the database to latest version'.PHP_EOL;
    echo '  db:version   Show current database version'.PHP_EOL;
    echo PHP_EOL;
    echo '  sanity:check Run sanity checks'.PHP_EOL;
    echo PHP_EOL;
    echo '  task:run     Run a task'.PHP_EOL;
    echo '               Usage: '.$scriptName.' task:run <TaskClass>'.PHP_EOL;

    exit();
}

$taskRunner = new TaskRunner($projectRoot);
$taskRunner->build();

try
{
    if ($command === 'task:run')
    {
        $taskClass = $argv[2] ?? null;
        if (!$taskClass)
        {
            echo 'Usage: '.$scriptName.' task:run <TaskClass>'.PHP_EOL;

            exit(1);
        }

        $task = new TaskRunnerTask($taskRunner, $taskClass);
        $taskRunner->executeTaskObject($task);

        exit();
    }

    // Map commands to task classes
    $commands = [
        'db:init' => 'WebFramework\Task\DbInitTask',
        'db:update' => 'WebFramework\Task\DbUpdateTask',
        'db:version' => 'WebFramework\Task\DbVersionTask',
        'sanity:check' => 'WebFramework\Task\SanityCheckTask',
    ];

    if (!isset($commands[$command]))
    {
        echo "Command '{$command}' does not exist.".PHP_EOL;

        exit(1);
    }

    $taskRunner->execute($commands[$command]);
}
catch (Throwable $e)
{
    echo 'Error: '.PHP_EOL;
    echo $e->getMessage().PHP_EOL;

    exit(1);
}
