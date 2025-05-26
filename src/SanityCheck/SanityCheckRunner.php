<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\SanityCheck;

use Carbon\Carbon;
use Psr\Container\ContainerInterface as Container;
use WebFramework\Core\Instrumentation;
use WebFramework\Exception\SanityCheckException;
use WebFramework\Support\StoredValuesService;

/**
 * Class SanityCheckRunner.
 *
 * Manages and executes sanity checks for the application.
 */
class SanityCheckRunner
{
    /** @var array<array{class: string, config: array<mixed>}> */
    private array $modules = [];
    private bool $forceRun = false;
    private bool $verbose = false;
    private bool $fixing = false;

    /**
     * SanityCheckRunner constructor.
     *
     * @param Container            $container           The dependency injection container
     * @param Instrumentation      $instrumentation     The instrumentation service
     * @param StoredValuesService  $storedValuesService The stored values service
     * @param array<string, mixed> $buildInfo           Information about the current build
     */
    public function __construct(
        private Container $container,
        private Instrumentation $instrumentation,
        private StoredValuesService $storedValuesService,
        private array $buildInfo,
    ) {}

    /**
     * Add a sanity check module to the runner.
     *
     * @param string       $moduleClass  The class name of the sanity check module
     * @param array<mixed> $moduleConfig Configuration for the module
     */
    public function add(string $moduleClass, array $moduleConfig): void
    {
        $this->modules[] = [
            'class' => $moduleClass,
            'config' => $moduleConfig,
        ];
    }

    /**
     * Execute all registered sanity checks.
     *
     * @return bool True if all checks pass, false otherwise
     *
     * @throws \RuntimeException If a sanity check fails
     */
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

            $span = $this->instrumentation->startSpan('sanity_check.'.$info['class']);
            $result = $module->performChecks();
            $this->instrumentation->finishSpan($span);

            if ($result === false)
            {
                throw new SanityCheckException('Sanity check failed');
            }
        }

        $this->registerRun($commit);

        return true;
    }

    /**
     * Determine if sanity checks need to be run.
     *
     * @param null|string $commit The current commit hash
     *
     * @return bool True if checks should be run, false otherwise
     */
    private function needsRun(?string $commit): bool
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
            $lastTimestamp = new Carbon((int) $this->storedValuesService->getValue('last_check', '0'));

            if (Carbon::now()->diffInSeconds($lastTimestamp) < 5)
            {
                return false;
            }

            $this->storedValuesService->setValue('last_check', (string) Carbon::now());

            return true;
        }

        // We are in an image
        // Only check if this commit was not yet successfully checked
        //
        $checked = $this->storedValuesService->getValue('checked_'.$commit, '0');

        return ($checked === '0');
    }

    /**
     * Register a successful run of sanity checks.
     *
     * @param null|string $commit The current commit hash
     */
    private function registerRun(?string $commit): void
    {
        // Register successful check of this commit
        //
        if ($commit !== null)
        {
            $this->storedValuesService->setValue('checked_'.$commit, '1');
        }
    }

    /**
     * Force the sanity checks to run regardless of other conditions.
     */
    public function forceRun(): void
    {
        $this->forceRun = true;
    }

    /**
     * Allow fixing of issues during sanity checks.
     */
    public function allowFixing(): void
    {
        $this->fixing = true;
    }

    /**
     * Enable verbose output during sanity checks.
     */
    public function setVerbose(): void
    {
        $this->verbose = true;
    }
}
