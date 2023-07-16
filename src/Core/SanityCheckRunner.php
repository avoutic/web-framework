<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use WebFramework\SanityCheck\SanityCheckModule;

class SanityCheckRunner
{
    /** @var array<array{class: string, config: array<mixed>}> */
    private array $modules = [];
    private bool $forceRun = false;
    private bool $verbose = false;
    private bool $fixing = false;

    /**
     * @param array<string, mixed> $buildInfo
     */
    public function __construct(
        private Container $container,
        private StoredValues $storedValues,
        private array $buildInfo,
    ) {
    }

    /**
     * @param array<mixed> $moduleConfig
     */
    public function add(string $moduleClass, array $moduleConfig): void
    {
        $this->modules[] = [
            'class' => $moduleClass,
            'config' => $moduleConfig,
        ];
    }

    public function execute(): bool
    {
        if (!count($this->modules))
        {
            return true;
        }

        $commit = $this->buildInfo['commit'];

        $needsRun = $this->needsRun($commit);
        if (!$needsRun)
        {
            return true;
        }

        foreach ($this->modules as $info)
        {
            $module = $this->container->get($info['class']);
            if (!($module instanceof SanityCheckModule))
            {
                throw new \RuntimeException("Class '{$info['class']}' in not a SanityCheckModule");
            }

            $module->setConfig($info['config']);

            if ($this->fixing)
            {
                $module->allowFixing();
            }

            if ($this->verbose)
            {
                $module->setVerbose();
            }

            $result = $module->performChecks();

            if ($result === false)
            {
                throw new \RuntimeException('Sanity check failed');
            }
        }

        $this->registerRun($commit);

        return true;
    }

    protected function needsRun(?string $commit): bool
    {
        if ($this->forceRun)
        {
            return true;
        }

        if ($commit == null)
        {
            // We are in live / development code.
            // Prevent flooding. Only start check once per
            // five seconds.
            //
            $lastTimestamp = (int) $this->storedValues->getValue('last_check', '0');

            if (time() - $lastTimestamp < 5)
            {
                return false;
            }

            $this->storedValues->setValue('last_check', (string) time());

            return true;
        }

        // We are in an image
        // Only check if this commit was not yet successfully checked
        //
        $checked = $this->storedValues->getValue('checked_'.$commit, '0');

        return ($checked === '0');
    }

    protected function registerRun(?string $commit): void
    {
        // Register successful check of this commit
        //
        if ($commit !== null)
        {
            $this->storedValues->setValue('checked_'.$commit, '1');
        }
    }

    public function forceRun(): void
    {
        $this->forceRun = true;
    }

    public function allowFixing(): void
    {
        $this->fixing = true;
    }

    public function setVerbose(): void
    {
        $this->verbose = true;
    }
}
