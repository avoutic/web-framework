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

use Psr\Container\ContainerInterface as Container;
use WebFramework\Core\BootstrapService;
use WebFramework\Core\DatabaseManager;
use WebFramework\Core\TaskInterface;

/**
 * Class DbInitTask.
 *
 * Task for initializing the database schema.
 */
class DbInitTask implements TaskInterface
{
    /**
     * DbInitTask constructor.
     *
     * @param Container        $container        The dependency injection container
     * @param BootstrapService $bootstrapService The bootstrap service
     * @param DatabaseManager  $databaseManager  The database manager service
     */
    public function __construct(
        private Container $container,
        private BootstrapService $bootstrapService,
        private DatabaseManager $databaseManager,
    ) {}

    /**
     * Execute the database initialization task.
     *
     * This method initializes the database schema if it hasn't been initialized yet.
     *
     * @throws \RuntimeException If the change set array is not found in the scheme file
     */
    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        $appDir = $this->container->get('app_dir');

        if ($this->databaseManager->isInitialized())
        {
            echo ' - Already initialized. Exiting.'.PHP_EOL;

            return;
        }

        $schemeFile = "{$appDir}/vendor/avoutic/web-framework/bootstrap/InitialScheme.php";
        $changeSet = $this->databaseManager->loadSchemaFile($schemeFile);
        $this->databaseManager->execute($changeSet, true);
    }
}
