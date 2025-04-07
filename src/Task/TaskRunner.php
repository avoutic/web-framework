<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Task;

use Carbon\Carbon;
use DI\Container;
use DI\ContainerBuilder;
use WebFramework\Core\ConfigBuilder;
use WebFramework\Exception\ArgumentParserException;

/**
 * Class TaskRunner.
 *
 * Manages the execution of tasks, including configuration and container setup.
 *
 * This class is not instantiated by the config or the Container, but initializes config and container for
 * the task to run.
 */
class TaskRunner
{
    private ConfigBuilder $configBuilder;

    /** @var ContainerBuilder<Container> */
    private ContainerBuilder $containerBuilder;
    private ?Container $container = null;
    private bool $isContinuous = false;
    private int $delayBetweenRunsInSecs = 1;
    private ?int $maxRuntimeInSecs = null;

    /** @var array<string> Default configuration files */
    private array $configFiles = [
        '/vendor/avoutic/web-framework/config/base_config.php',
        '/config/config.php',
        '?/config/config_local.php',
    ];

    /** @var ?array<string> */
    private ?array $definitionFiles = null;

    /**
     * TaskRunner constructor.
     *
     * @param string $appDir The application directory
     */
    public function __construct(
        private string $appDir,
    ) {
        $this->configBuilder = new ConfigBuilder($this->appDir);
        $this->containerBuilder = new ContainerBuilder();
    }

    /**
     * Set the configuration files to use.
     *
     * @param array<string> $configs An array of configuration file paths
     */
    public function setConfigFiles(array $configs): void
    {
        $this->configFiles = $configs;
    }

    /**
     * Set the definition files to use.
     *
     * @param array<string> $definitions An array of definition file paths
     */
    public function setDefinitionFiles(array $definitions): void
    {
        $this->definitionFiles = $definitions;
    }

    /**
     * Get a service from the container.
     *
     * @param string $key The service key
     *
     * @return mixed The requested service
     *
     * @throws \RuntimeException If the container is not initialized
     */
    public function get(string $key): mixed
    {
        if (!$this->container)
        {
            throw new \RuntimeException('Container not yet initialized');
        }

        return $this->container->get($key);
    }

    /**
     * Set the task to run continuously.
     */
    public function setContinuous(): void
    {
        $this->isContinuous = true;
    }

    /**
     * Set the delay between continuous runs.
     *
     * @param int $secs The delay in seconds
     */
    public function setDelayBetweenRuns(int $secs): void
    {
        $this->delayBetweenRunsInSecs = $secs;
    }

    /**
     * Set the maximum runtime for continuous execution.
     *
     * @param int $secs The maximum runtime in seconds
     */
    public function setMaxRunTime(int $secs): void
    {
        $this->maxRuntimeInSecs = $secs;
    }

    /**
     * Build the configuration and container.
     */
    public function build(): void
    {
        // Build config
        //
        $config = $this->configBuilder->buildConfig(
            $this->configFiles,
        );

        // Build container
        //
        $this->containerBuilder->addDefinitions(['config_tree' => $this->configBuilder->getConfig()]);
        $this->containerBuilder->addDefinitions($this->configBuilder->getFlattenedConfig());

        $definitionFiles = $this->definitionFiles ?? $config['definition_files'];
        foreach ($definitionFiles as $file)
        {
            $this->containerBuilder->addDefinitions("{$this->appDir}/definitions/{$file}");
        }

        $this->container = $this->containerBuilder->build();
    }

    /**
     * Apply arguments to a task.
     *
     * @param ConsoleTask   $task                 The task to apply arguments to
     * @param array<string> $commandLineArguments The arguments to apply
     *
     * @throws ArgumentParserException If the arguments are invalid
     */
    private function applyArguments(ConsoleTask $task, array $commandLineArguments): void
    {
        $options = $task->getOptions();

        $arguments = $task->getArguments();
        $argumentIndex = 0;

        $i = 0;
        $argCount = count($commandLineArguments);
        while ($i < $argCount)
        {
            $arg = $commandLineArguments[$i];

            if (str_starts_with($arg, '--'))
            {
                $searchKey = 'long';
                $searchValue = substr($arg, 2);

                $result = array_filter($options, function ($item) use ($searchKey, $searchValue) {
                    return $item[$searchKey] === $searchValue;
                });

                $data = reset($result);

                if (!$data)
                {
                    throw new ArgumentParserException("Unknown option: {$arg}");
                }

                if ($data['has_value'])
                {
                    if ($i + 1 >= $argCount)
                    {
                        throw new ArgumentParserException("Option {$arg} requires a value");
                    }

                    $data['setter']($commandLineArguments[$i + 1]);
                    $i++;
                }
                else
                {
                    $data['setter']();
                }
            }
            elseif (str_starts_with($arg, '-'))
            {
                $searchKey = 'short';
                $searchValue = substr($arg, 1);

                $result = array_filter($options, function ($item) use ($searchKey, $searchValue) {
                    return isset($item[$searchKey]) && $item[$searchKey] === $searchValue;
                });

                $data = reset($result);

                if (!$data)
                {
                    throw new ArgumentParserException("Unknown option: {$arg}");
                }

                if ($data['has_value'])
                {
                    if ($i + 1 >= $argCount)
                    {
                        throw new ArgumentParserException("Option {$arg} requires a value");
                    }

                    $data['setter']($commandLineArguments[$i + 1]);
                    $i++;
                }
                else
                {
                    $data['setter']();
                }
            }
            else
            {
                if ($argumentIndex >= count($arguments))
                {
                    throw new ArgumentParserException('Too many arguments');
                }

                $data = $arguments[$argumentIndex];
                $data['setter']($arg);
                $argumentIndex++;
            }

            $i++;
        }

        if ($argumentIndex < count($arguments))
        {
            throw new ArgumentParserException('Missing arguments');
        }
    }

    /**
     * Execute a task.
     *
     * @param string        $taskClass The fully qualified class name of the task to execute
     * @param array<string> $arguments The arguments to pass to the task
     *
     * @throws \RuntimeException       If the task does not implement Task
     * @throws ArgumentParserException If the arguments are invalid
     */
    public function execute(string $taskClass, array $arguments = []): void
    {
        $task = $this->get($taskClass);

        if (!$task instanceof Task)
        {
            throw new \RuntimeException("Task {$taskClass} does not implement Task");
        }

        $this->executeTaskObject($task, $arguments);
    }

    /**
     * Execute a task object.
     *
     * @param Task          $task      The task object to execute
     * @param array<string> $arguments The arguments to pass to the task
     *
     * @throws \RuntimeException       If the task does not implement Task
     * @throws ArgumentParserException If the arguments are invalid
     */
    public function executeTaskObject(Task $task, array $arguments = []): void
    {
        if ($task instanceof ConsoleTask)
        {
            $this->applyArguments($task, $arguments);
        }

        if ($this->isContinuous)
        {
            $start = Carbon::now();

            while (true)
            {
                $task->execute();

                if ($this->maxRuntimeInSecs)
                {
                    if ($start->diffInSeconds() > $this->maxRuntimeInSecs)
                    {
                        break;
                    }
                }

                Carbon::sleep($this->delayBetweenRunsInSecs);
            }
        }
        else
        {
            $task->execute();
        }
    }
}
