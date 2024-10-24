<?php

namespace WebFramework\Core;

use DI\Container;
use DI\ContainerBuilder;

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
        '/config/base_config.php',
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
     * Execute a task.
     *
     * @param string $taskClass The fully qualified class name of the task to execute
     *
     * @throws \RuntimeException If the task does not implement TaskInterface
     */
    public function execute(string $taskClass): void
    {
        $task = $this->get($taskClass);
        if (!$task instanceof TaskInterface)
        {
            throw new \RuntimeException("Task {$taskClass} does not implement TaskInterface");
        }

        if ($this->isContinuous)
        {
            $start = time();

            while (true)
            {
                $task->execute();

                if ($this->maxRuntimeInSecs)
                {
                    if (time() > $start + $this->maxRuntimeInSecs)
                    {
                        break;
                    }
                }

                sleep($this->delayBetweenRunsInSecs);
            }
        }
        else
        {
            $task->execute();
        }
    }
}
