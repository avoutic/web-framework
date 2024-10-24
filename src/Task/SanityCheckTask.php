<?php

namespace WebFramework\Task;

use WebFramework\Core\BootstrapService;
use WebFramework\Core\TaskInterface;

class SanityCheckTask implements TaskInterface
{
    public function __construct(
        private BootstrapService $bootstrapService,
    ) {}

    public function execute(): void
    {
        $this->bootstrapService->setSanityCheckFixing();
        $this->bootstrapService->setSanityCheckVerbose();
        $this->bootstrapService->setSanityCheckForceRun();

        $this->bootstrapService->bootstrap();
    }
}
