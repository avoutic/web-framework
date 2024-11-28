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
use WebFramework\Core\ConfigService;
use WebFramework\Core\DatabaseManager;
use WebFramework\Core\TaskInterface;

/**
 * Task for updating the database schema.
 */
class DbUpdateTask implements TaskInterface
{
    /**
     * DbUpdateTask constructor.
     *
     * @param Container        $container        The dependency injection container
     * @param BootstrapService $bootstrapService The bootstrap service
     * @param ConfigService    $configService    The configuration service
     * @param DatabaseManager  $databaseManager  The database manager service
     */
    public function __construct(
        private Container $container,
        private BootstrapService $bootstrapService,
        private ConfigService $configService,
        private DatabaseManager $databaseManager,
    ) {}

    /**
     * Execute the database update task.
     *
     * This method applies the schema changes defined in the update data.
     */
    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        $currentVersion = $this->databaseManager->getCurrentVersion();
        $requiredVersion = (int) $this->configService->get('versions.required_app_db');

        $appDir = $this->container->get('app_dir');

        if ($requiredVersion <= $currentVersion)
        {
            echo 'Nothing to be done'.PHP_EOL;

            return;
        }

        // Retrieve relevant change set
        //
        $nextVersion = $currentVersion + 1;

        while ($nextVersion <= $requiredVersion)
        {
            $versionFile = "{$appDir}/db_scheme/{$nextVersion}.php";

            if (!file_exists($versionFile))
            {
                $versionFile = "{$appDir}/db_scheme/{$nextVersion}.inc.php";

                if (!file_exists($versionFile))
                {
                    echo " - No changeset for {$nextVersion} available".PHP_EOL;

                    return;
                }
            }

            $changeSet = $this->databaseManager->loadSchemaFile($versionFile);
            $this->databaseManager->execute($changeSet);
            $currentVersion = $this->databaseManager->getCurrentVersion();
            $nextVersion = $currentVersion + 1;
        }
    }
}
