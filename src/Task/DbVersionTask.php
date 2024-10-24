<?php

namespace WebFramework\Task;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Core\BootstrapService;
use WebFramework\Core\DatabaseManager;
use WebFramework\Core\TaskInterface;

class DbVersionTask implements TaskInterface
{
    public function __construct(
        private Container $container,
        private BootstrapService $bootstrapService,
        private DatabaseManager $databaseManager,
    ) {}

    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        $appDir = $this->container->get('app_dir');

        // Verify database scheme hash
        //
        print_r($this->databaseManager->verifyHash());
    }
}
