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

use WebFramework\Core\DebugService;
use WebFramework\Exception\ArgumentParserException;
use WebFramework\Task\TaskRunner;
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
$arguments = array_slice($argv, 2);

// Map supported commands
//
$commands = [
    'db:init' => 'WebFramework\Task\DbInitTask',
    'db:update' => 'WebFramework\Task\DbUpdateTask',
    'db:version' => 'WebFramework\Task\DbVersionTask',
    'queue:list' => 'WebFramework\Task\QueueList',
    'queue:worker' => 'WebFramework\Task\QueueWorker',
    'sanity:check' => 'WebFramework\Task\SanityCheckTask',
    'task:run' => 'WebFramework\Task\TaskRunnerTask',
];

function showUsage(string $scriptName): void
{
    echo 'Usage: '.$scriptName.' <command> [options]'.PHP_EOL.PHP_EOL;
    echo 'Available commands:'.PHP_EOL;
    echo '  help         Show this help'.PHP_EOL;
    echo PHP_EOL;
    echo '  db:init      Initialize the database'.PHP_EOL;
    echo '  db:update    Update the database to latest version'.PHP_EOL;
    echo '               --dry-run    Dry run the task (no changes will be made)'.PHP_EOL;
    echo '  db:version   Show current database version'.PHP_EOL;
    echo PHP_EOL;
    echo '  queue:list   List all queues'.PHP_EOL;
    echo '  queue:worker Run a queue worker'.PHP_EOL;
    echo '               Usage: '.$scriptName.' queue:worker <QueueName>'.PHP_EOL;
    echo '               --max-jobs   The maximum number of jobs to process'.PHP_EOL;
    echo '               --max-runtime The maximum runtime of the worker'.PHP_EOL;
    echo PHP_EOL;
    echo '  sanity:check Run sanity checks'.PHP_EOL;
    echo PHP_EOL;
    echo '  task:run     Run a task'.PHP_EOL;
    echo '               Usage: '.$scriptName.' task:run <TaskClass>'.PHP_EOL;

    exit();
}

if (!$command || !isset($commands[$command]))
{
    showUsage($scriptName);
}

$taskRunner = new TaskRunner($projectRoot);
$taskRunner->build();

try
{
    if ($command === 'task:run')
    {
        $task = new TaskRunnerTask($taskRunner);
        $taskRunner->executeTaskObject($task, $arguments);
    }
    else
    {
        $taskRunner->execute($commands[$command], $arguments);
    }
}
catch (ArgumentParserException $e)
{
    echo $e->getMessage().PHP_EOL.PHP_EOL;

    showUsage($scriptName);

    exit(1);
}
catch (Throwable $e)
{
    $debug = $taskRunner->get('debug');

    if ($debug)
    {
        $debugService = $taskRunner->get(DebugService::class);
        $errorReport = $debugService->getThrowableReport($e);

        echo $errorReport->message.PHP_EOL;

        exit(1);
    }

    echo 'Error: '.PHP_EOL;
    echo $e->getMessage().PHP_EOL;

    exit(1);
}
