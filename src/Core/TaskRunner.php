<?php

namespace WebFramework\Core;

use DI\Container;
use DI\ContainerBuilder;

/**
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

    /** @var array<string> */
    private array $configFiles = [
        '/config/base_config.php',
        '/config/config.php',
        '?/config/config_local.php',
    ];

    /** @var ?array<string> */
    private ?array $definitionFiles = null;

    public function __construct(
        private string $appDir,
    ) {
        $this->configBuilder = new ConfigBuilder($this->appDir);
        $this->containerBuilder = new ContainerBuilder();
    }

    /**
     * @param array<string> $configs
     */
    public function setConfigFiles(array $configs): void
    {
        $this->configFiles = $configs;
    }

    /**
     * @param array<string> $definitions
     */
    public function setDefinitionFiles(array $definitions): void
    {
        $this->definitionFiles = $definitions;
    }

    public function get(string $key): mixed
    {
        if (!$this->container)
        {
            throw new \RuntimeException('Container not yet initialized');
        }

        return $this->container->get($key);
    }

    public function setContinuous(): void
    {
        $this->isContinuous = true;
    }

    public function setDelayBetweenRuns(int $secs): void
    {
        $this->delayBetweenRunsInSecs = $secs;
    }

    public function setMaxRunTime(int $secs): void
    {
        $this->maxRuntimeInSecs = $secs;
    }

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
