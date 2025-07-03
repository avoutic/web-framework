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

use WebFramework\Core\BootstrapService;
use WebFramework\Exception\SanityCheckException;

/**
 * Class SanityCheckTask.
 *
 * This task is responsible for running sanity checks on the application.
 */
class SanityCheckTask extends ConsoleTask
{
    /**
     * SanityCheckTask constructor.
     *
     * @param BootstrapService $bootstrapService The bootstrap service
     */
    public function __construct(
        private BootstrapService $bootstrapService,
    ) {}

    public function getCommand(): string
    {
        return 'sanity:check';
    }

    public function getDescription(): string
    {
        return 'Run sanity checks on the application';
    }

    /**
     * Execute the sanity check task.
     *
     * This method configures the bootstrap service to run sanity checks with specific settings,
     * then bootstraps the application.
     */
    public function execute(): void
    {
        $this->bootstrapService->setSanityCheckFixing();
        $this->bootstrapService->setSanityCheckVerbose();
        $this->bootstrapService->setSanityCheckForceRun();

        try
        {
            $this->bootstrapService->bootstrap();
        }
        catch (SanityCheckException $e)
        {
            // No need to handle this exception when explicitly expected
        }
    }

    /**
     * Check if the task handles its own bootstrapping.
     *
     * @return bool True if the task handles its own bootstrapping, false otherwise
     */
    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }
}
