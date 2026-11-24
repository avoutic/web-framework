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

use DI\Container;
use DI\ContainerBuilder;
use WebFramework\Config\ConfigBuilder;
use WebFramework\Core\BootstrapService;
use WebFramework\Core\EnvLoader;
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
    private bool $isPlaintext = false;

    /** @var ?array<string> Configuration files set with setConfigFiles(), null for the defaults */
    private ?array $configFiles = null;

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
     * @template T of object
     *
     * @param class-string<T>|string $key The service key: a class name, or the name of another entry
     *
     * @return ($key is class-string<T> ? T : mixed) The requested service. For a class name this
     *                                               is an instance of that class
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
     * Set the task to run in plaintext mode.
     */
    public function setPlaintext(): void
    {
        $this->isPlaintext = true;
    }

    /**
     * Get the configuration files to use.
     *
     * Without files set with setConfigFiles(), these are the base configuration of the
     * framework, the configuration of the application, the optional configuration for the
     * environment in APP_ENV, and the optional local configuration.
     *
     * @return array<string> The configuration file paths
     */
    public function getConfigFiles(): array
    {
        if ($this->configFiles !== null)
        {
            return $this->configFiles;
        }

        $files = [
            '/vendor/avoutic/web-framework/config/base_config.php',
            '/config/config.php',
        ];

        // The name ends up in a path, so only accept a plain name. 'local' is the local
        // configuration itself
        //
        $appEnv = getenv('APP_ENV');
        if ($appEnv !== false && $appEnv !== 'local' && preg_match('/^[A-Za-z0-9_-]+$/', $appEnv))
        {
            $files[] = "?/config/config_{$appEnv}.php";
        }

        $files[] = '?/config/config_local.php';

        return $files;
    }

    public function loadEnv(): void
    {
        $envLoader = new EnvLoader();
        $envFile = "{$this->appDir}/.env";

        $appEnv = getenv('APP_ENV');
        if ($appEnv !== false)
        {
            if (file_exists("{$this->appDir}/.env.{$appEnv}"))
            {
                $envFile = "{$this->appDir}/.env.{$appEnv}";
            }
        }

        $envLoader->loadEnvFile($envFile);

        // Normally already loaded by the Composer autoloader. Kept for when the framework
        // is loaded in another way
        //
        require_once __DIR__.'/../Environment.php';
    }

    /**
     * Resolve an entry of the definition files to the path of the file.
     *
     * - A path that starts with '/' is relative to the application directory, like the
     *   configuration files are.
     * - Any other path is relative to the definitions directory of the application.
     * - A '?' in front makes the file optional.
     *
     * @param string $file The entry from the definition files
     *
     * @return null|string The path of the file, or null for an optional file that does not exist
     */
    public function resolveDefinitionFile(string $file): ?string
    {
        $optional = str_starts_with($file, '?');

        if ($optional)
        {
            $file = substr($file, 1);
        }

        if (str_starts_with($file, '/'))
        {
            $path = "{$this->appDir}{$file}";

            // Before, every path was relative to the definitions directory, also with a
            // slash in front. Keep those entries working
            //
            if (!file_exists($path) && file_exists("{$this->appDir}/definitions{$file}"))
            {
                $path = "{$this->appDir}/definitions{$file}";
            }
        }
        else
        {
            $path = "{$this->appDir}/definitions/{$file}";
        }

        if ($optional && !file_exists($path))
        {
            return null;
        }

        return $path;
    }

    /**
     * Build the configuration and container.
     */
    public function build(): void
    {
        $this->loadEnv();

        $config = $this->configBuilder->buildConfig(
            $this->getConfigFiles(),
        );

        // Build container
        //
        $this->containerBuilder->addDefinitions(['config_tree' => $this->configBuilder->getConfig()]);
        $this->containerBuilder->addDefinitions($this->configBuilder->getFlattenedConfig());
        $this->containerBuilder->addDefinitions(['is_plaintext' => $this->isPlaintext]);
        $this->containerBuilder->addDefinitions([self::class => $this]);

        $definitionFiles = $this->definitionFiles ?? $config['definition_files'];
        foreach ($definitionFiles as $file)
        {
            $path = $this->resolveDefinitionFile($file);

            if ($path !== null)
            {
                $this->containerBuilder->addDefinitions($path);
            }
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
        /** @var array<TaskOption> $options */
        $options = $task->getOptions();

        /** @var array<TaskArgument> $arguments */
        $arguments = $task->getArguments();
        $argumentIndex = 0;

        $i = 0;
        $argCount = count($commandLineArguments);
        while ($i < $argCount)
        {
            $arg = $commandLineArguments[$i];

            if (str_starts_with($arg, '--'))
            {
                $searchValue = substr($arg, 2);
                $inlineValue = null;

                $equalsPosition = strpos($searchValue, '=');
                if ($equalsPosition !== false)
                {
                    $inlineValue = substr($searchValue, $equalsPosition + 1);
                    $searchValue = substr($searchValue, 0, $equalsPosition);
                }

                $option = $this->findOptionByLong($options, $searchValue);

                if (!$option)
                {
                    throw new ArgumentParserException("Unknown option: {$arg}");
                }

                if ($option->hasValue())
                {
                    if ($inlineValue !== null)
                    {
                        $option->applyValue($inlineValue);
                    }
                    else
                    {
                        if ($i + 1 >= $argCount)
                        {
                            throw new ArgumentParserException("Option {$arg} requires a value");
                        }

                        $option->applyValue($commandLineArguments[$i + 1]);
                        $i++;
                    }
                }
                else
                {
                    if ($inlineValue !== null)
                    {
                        throw new ArgumentParserException("Option {$arg} does not accept a value");
                    }

                    $option->trigger();
                }
            }
            elseif (str_starts_with($arg, '-'))
            {
                $searchValue = substr($arg, 1);

                $option = $this->findOptionByShort($options, $searchValue);

                if (!$option)
                {
                    throw new ArgumentParserException("Unknown option: {$arg}");
                }

                if ($option->hasValue())
                {
                    if ($i + 1 >= $argCount)
                    {
                        throw new ArgumentParserException("Option {$arg} requires a value");
                    }

                    $option->applyValue($commandLineArguments[$i + 1]);
                    $i++;
                }
                else
                {
                    $option->trigger();
                }
            }
            else
            {
                if ($argumentIndex >= count($arguments))
                {
                    throw new ArgumentParserException('Too many arguments');
                }

                $argument = $arguments[$argumentIndex];
                $argument->apply($arg);
                $argumentIndex++;
            }

            $i++;
        }

        for ($remaining = $argumentIndex; $remaining < count($arguments); $remaining++)
        {
            if ($arguments[$remaining]->isRequired())
            {
                throw new ArgumentParserException('Missing arguments');
            }
        }
    }

    /**
     * Locate an option by its long name.
     *
     * @param array<TaskOption> $options
     */
    private function findOptionByLong(array $options, string $long): ?TaskOption
    {
        foreach ($options as $option)
        {
            if ($option->getLong() === $long)
            {
                return $option;
            }
        }

        return null;
    }

    /**
     * Locate an option by its short name.
     *
     * @param array<TaskOption> $options
     */
    private function findOptionByShort(array $options, string $short): ?TaskOption
    {
        foreach ($options as $option)
        {
            if ($option->getShort() === $short)
            {
                return $option;
            }
        }

        return null;
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

        // Bootstrap the application if the task doesn't handle its own bootstrapping
        if (!$task->handlesOwnBootstrapping())
        {
            $bootstrapService = $this->get(BootstrapService::class);
            $bootstrapService->bootstrap();
        }

        $task->execute();
    }
}
