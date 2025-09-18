<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

use WebFramework\Exception\ArgumentParserException;
use WebFramework\Task\TaskArgument;
use WebFramework\Task\TaskOption;
use WebFramework\Task\TaskRunner;

/**
 * Handles console command dispatching and usage rendering for the framework entry point.
 */
class ConsoleApplication
{
    /**
     * @param resource $outputStream stream used for CLI output
     */
    public function __construct(
        private DebugService $debugService,
        private TaskRunner $taskRunner,
        private ConsoleTaskRegistryService $taskRegistry,
        private bool $debug,
        private $outputStream = STDOUT,
    ) {}

    private string $scriptName = 'framework';

    /**
     * Run the console application.
     *
     * @param array<string> $argv full argument vector including script name
     */
    public function run(array $argv): int
    {
        $this->scriptName = basename($argv[0] ?? 'framework');
        array_shift($argv);

        $command = $argv[0] ?? null;
        $commandArguments = array_slice($argv, 1);

        try
        {
            if ($command === null || ($command === 'help' && count($commandArguments) === 0))
            {
                $this->showUsage();

                return 0;
            }

            if ($command === 'help' && count($commandArguments) === 1)
            {
                return $this->showCommandHelp($commandArguments[0]);
            }

            $task = $this->taskRegistry->getTaskForCommand($command);
            if (!$task)
            {
                $this->showUsage();

                return 0;
            }

            $this->taskRunner->executeTaskObject($task, $commandArguments);

            return 0;
        }
        catch (ArgumentParserException $e)
        {
            $this->write($e->getMessage().PHP_EOL.PHP_EOL);
            $this->showUsage();

            return 1;
        }
        catch (\Throwable $e)
        {
            if ($this->debug)
            {
                $errorReport = $this->debugService->getThrowableReport($e);

                $this->write($errorReport->message.PHP_EOL);

                return 1;
            }

            $this->write('Error: '.PHP_EOL);
            $this->write($e->getMessage().PHP_EOL);

            return 1;
        }
    }

    /**
     * Print a command.
     *
     * @param array<TaskArgument> $arguments
     * @param array<TaskOption>   $options
     */
    private function printCommand(string $command, ?string $description, array $arguments = [], array $options = []): void
    {
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

        $descriptionText = $description ?? '';

        if (strlen($line) < 30)
        {
            $line .= str_repeat(' ', 30 - strlen($line));
        }
        else
        {
            $line .= PHP_EOL.str_repeat(' ', 30);
        }

        $this->write($line.$descriptionText.PHP_EOL);

        if (!empty($options))
        {
            foreach ($options as $option)
            {
                if (!$option instanceof TaskOption)
                {
                    continue;
                }

                $line = '    ';
                if ($option->getShort())
                {
                    $line .= '-'.$option->getShort().', --'.$option->getLong();
                }
                else
                {
                    $line .= '    --'.$option->getLong();
                }

                if ($option->hasValue())
                {
                    $line .= ' <value>';
                }

                $line .= strlen($line) < 30 ? str_repeat(' ', 30 - strlen($line)) : ' ';
                $line .= $option->getDescription().PHP_EOL;

                $this->write($line);
            }
        }
    }

    private function showUsage(): void
    {
        $this->write('Usage: '.$this->scriptName.' <command> [options]'.PHP_EOL.PHP_EOL);
        $this->write('Available commands:'.PHP_EOL);
        $this->write('  help           Show this help'.PHP_EOL);
        $this->write('  help <command> Show help for a command'.PHP_EOL);

        $frameworkTasks = $this->taskRegistry->getFrameworkTasks();
        $lastCommandModule = '';
        foreach ($frameworkTasks as $command => $task)
        {
            $commandModule = substr($command, 0, strpos($command, ':') ?: -1);
            if ($commandModule !== $lastCommandModule)
            {
                $this->write(PHP_EOL);
                $lastCommandModule = $commandModule;
            }

            $task = $this->taskRegistry->getTaskForCommand($command);
            if (!$task)
            {
                continue;
            }

            $this->printCommand(
                $command,
                $task->getDescription(),
                $task->getArguments(),
                $task->getOptions()
            );
        }

        $appTasks = $this->taskRegistry->getAppTasks();
        if (!empty($appTasks))
        {
            $this->write(PHP_EOL);
            $this->write('App commands:'.PHP_EOL);

            foreach ($appTasks as $command => $task)
            {
                $task = $this->taskRegistry->getTaskForCommand($command);

                if (!$task)
                {
                    continue;
                }

                $this->printCommand(
                    $command,
                    $task->getDescription(),
                    $task->getArguments(),
                    $task->getOptions()
                );
            }
        }
    }

    private function showCommandHelp(string $command): int
    {
        $task = $this->taskRegistry->getTaskForCommand($command);

        if ($task)
        {
            $this->write($task->getUsage().PHP_EOL);

            return 0;
        }

        $this->showUsage();

        return 0;
    }

    private function write(string $message): void
    {
        fwrite($this->outputStream, $message);
    }
}
