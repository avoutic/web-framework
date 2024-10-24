<?php

namespace WebFramework\Task;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Core\BootstrapService;
use WebFramework\Core\ConfigService;
use WebFramework\Core\DatabaseManager;
use WebFramework\Core\TaskInterface;

class DbUpdateTask implements TaskInterface
{
    public function __construct(
        private Container $container,
        private BootstrapService $bootstrapService,
        private ConfigService $configService,
        private DatabaseManager $databaseManager,
    ) {}

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

            $changeSet = require $versionFile;
            if (!is_array($changeSet))
            {
                throw new \RuntimeException('No change set array found');
            }

            $this->databaseManager->execute($changeSet);
            $currentVersion = $this->databaseManager->getCurrentVersion();
            $nextVersion = $currentVersion + 1;
        }
    }
}
