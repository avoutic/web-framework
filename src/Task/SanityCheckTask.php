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
use WebFramework\Core\Task;

/**
 * Class SanityCheckTask.
 *
 * This task is responsible for running sanity checks on the application.
 */
class SanityCheckTask implements Task
{
    /**
     * SanityCheckTask constructor.
     *
     * @param BootstrapService $bootstrapService The bootstrap service
     */
    public function __construct(
        private BootstrapService $bootstrapService,
    ) {}

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

        $this->bootstrapService->bootstrap();
    }
}
