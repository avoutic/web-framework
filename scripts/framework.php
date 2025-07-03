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

use WebFramework\Core\ConsoleTaskRegistryService;
use WebFramework\Core\DebugService;
use WebFramework\Exception\ArgumentParserException;
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

// Get command from command line
$scriptName = basename($argv[0]);
$command = $argv[1] ?? null;
$arguments = array_slice($argv, 2);

$taskRunner = new TaskRunner($projectRoot);
$taskRunner->setPlaintext();
$taskRunner->build();

$taskRegistry = $taskRunner->get(ConsoleTaskRegistryService::class);

function showUsage(string $scriptName): void
{
    global $taskRegistry;

    echo 'Usage: '.$scriptName.' <command> [options]'.PHP_EOL.PHP_EOL;
    echo 'Available commands:'.PHP_EOL;
    echo '  help         Show this help'.PHP_EOL;
    echo '  help <command> Show help for a command'.PHP_EOL;
    echo PHP_EOL;
    echo '  db:migrate   Run pending database migrations'.PHP_EOL;
    echo '               --dry-run    Dry run the task (no changes will be made)'.PHP_EOL;
    echo '               --framework  Run framework migrations only'.PHP_EOL;
    echo '  db:convert-from-scheme Convert from old db_scheme system to new migrations system'.PHP_EOL;
    echo '               --dry-run    Show what would be done without making changes'.PHP_EOL;
    echo '  db:convert-production Convert production database to migration system'.PHP_EOL;
    echo '               --dry-run    Show what would be done without making changes'.PHP_EOL;
    echo '  db:status    Show database migration status'.PHP_EOL;
    echo '  db:make      Generate a new migration file'.PHP_EOL;
    echo '               --framework  Create a framework migration'.PHP_EOL;
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
    echo '               --continuous      Run the task continuously'.PHP_EOL;
    echo '               --delay <secs>    The delay between continuous runs in seconds'.PHP_EOL;
    echo '               --max-runtime <secs> The maximum runtime in seconds'.PHP_EOL;

    $appTasks = $taskRegistry->getAppTasks();
    if (!empty($appTasks))
    {
        echo PHP_EOL;
        echo 'App commands:'.PHP_EOL;
        foreach ($appTasks as $command => $task)
        {
            $task = $taskRegistry->getTaskForCommand($command);

            if (!$task)
            {
                continue;
            }

            $description = $task->getDescription();
            echo "  {$command}";
            if ($description)
            {
                echo "   {$description}";
            }
            echo PHP_EOL;
        }
    }

    exit();
}

try
{
    if (!$command || ($command === 'help' && count($arguments) === 0))
    {
        showUsage($scriptName);
    }

    if ($command === 'help' && count($arguments) === 1)
    {
        $command = $arguments[0];
        $task = $taskRegistry->getTaskForCommand($command);

        if ($task)
        {
            echo $task->getUsage().PHP_EOL;

            exit();
        }

        showUsage($scriptName);
    }

    $task = null;

    $task = $taskRegistry->getTaskForCommand($command);

    if (!$task)
    {
        showUsage($scriptName);
    }

    $taskRunner->executeTaskObject($task, $arguments);
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
