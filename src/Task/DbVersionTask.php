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
use WebFramework\Core\Database;
use WebFramework\Core\DatabaseManager;

/**
 * Class DbVersionTask.
 *
 * This task is responsible for verifying the database scheme hash.
 */
class DbVersionTask implements Task
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

        $requiredWfDbVersion = $this->databaseManager->getRequiredFrameworkVersion();
        $requiredAppDbVersion = $this->databaseManager->getRequiredAppVersion();

        $currentWfDbVersion = $this->databaseManager->getCurrentFrameworkVersion();
        $currentAppDbVersion = $this->databaseManager->getCurrentAppVersion();

        echo 'Required WebFramework DB Version: '.$requiredWfDbVersion.PHP_EOL;
        echo ' Current WebFramework DB Version: '.$currentWfDbVersion.PHP_EOL;

        echo 'Required App DB Version: '.$requiredAppDbVersion.PHP_EOL;
        echo ' Current App DB Version: '.$currentAppDbVersion.PHP_EOL;
    }
}
