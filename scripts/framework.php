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
use WebFramework\Task\TaskArgument;
use WebFramework\Task\TaskOption;
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

    $printCommand = static function (string $command, ?string $description, array $arguments = [], array $options = []): void {
        $stdPadStr = str_repeat(' ', 30);
        $line = ' '.$command;

        if (!empty($arguments))
        {
            foreach ($arguments as $argument)
            {
                if (!$argument instanceof TaskArgument)
                {
                    continue;
                }

                if ($argument->isRequired())
                {
                    $line .= ' <'.$argument->getName().'>';
                }
                else
                {
                    $line .= ' ['.$argument->getName().']';
                }
            }
        }

        if (strlen($line) < 30)
        {
            $line .= str_repeat(' ', 30 - strlen($line));
        }
        else
        {
            $line .= PHP_EOL.$stdPadStr;
        }

        echo $line.$description.PHP_EOL;

        if (!empty($options))
        {
            foreach ($options as $option)
            {
                if (!$option instanceof TaskOption)
                {
                    continue;
                }

                $line = '';
                if ($option->getShort())
                {
                    $line .= '    -'.$option->getShort().', --'.$option->getLong();
                }
                else
                {
                    $line .= '        --'.$option->getLong();
                }
                if ($option->hasValue())
                {
                    $line .= ' <value>';
                }

                $line .= strlen($line) < 30 ? str_repeat(' ', 30 - strlen($line)) : ' ';
                $line .= $option->getDescription().PHP_EOL;

                echo $line;
            }
        }
    };

    echo 'Usage: '.$scriptName.' <command> [options]'.PHP_EOL.PHP_EOL;
    echo 'Available commands:'.PHP_EOL;
    echo '  help           Show this help'.PHP_EOL;
    echo '  help <command> Show help for a command'.PHP_EOL;

    $frameworkTasks = $taskRegistry->getFrameworkTasks();
    $lastCommandModule = '';
    foreach ($frameworkTasks as $command => $task)
    {
        $commandModule = substr($command, 0, strpos($command, ':') ?: -1);
        if ($commandModule !== $lastCommandModule)
        {
            echo PHP_EOL;
            $lastCommandModule = $commandModule;
        }

        $task = $taskRegistry->getTaskForCommand($command);
        $description = $task->getDescription();
        $arguments = $task->getArguments();
        $options = $task->getOptions();
        $printCommand($command, $description, $arguments, $options);
    }

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
            $arguments = $task->getArguments();
            $options = $task->getOptions();
            $printCommand($command, $description, $arguments, $options);
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
