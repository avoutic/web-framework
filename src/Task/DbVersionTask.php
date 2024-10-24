<?php

namespace WebFramework\Task;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Core\BootstrapService;
use WebFramework\Core\DatabaseManager;
use WebFramework\Core\TaskInterface;

/**
 * Class DbVersionTask.
 *
 * This task is responsible for verifying the database scheme hash.
 */
class DbVersionTask implements TaskInterface
{
    /**
     * DbVersionTask constructor.
     *
     * @param Container        $container        The dependency injection container
     * @param BootstrapService $bootstrapService The bootstrap service
     * @param DatabaseManager  $databaseManager  The database manager
     */
    public function __construct(
        private Container $container,
        private BootstrapService $bootstrapService,
        private DatabaseManager $databaseManager,
    ) {}

    /**
     * Execute the database version verification task.
     *
     * This method skips sanity checks, bootstraps the application, and verifies the database scheme hash.
     */
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
