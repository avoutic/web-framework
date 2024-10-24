<?php

namespace WebFramework\Task;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Core\BootstrapService;
use WebFramework\Core\DatabaseManager;
use WebFramework\Core\TaskInterface;

class DbInitTask implements TaskInterface
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

        if ($this->databaseManager->isInitialized())
        {
            echo ' - Already initialized. Exiting.'.PHP_EOL;

            return;
        }

        $schemeFile = "{$appDir}/vendor/avoutic/web-framework/bootstrap/InitialScheme.php";

        if (!file_exists($schemeFile))
        {
            echo " - Scheme file {$schemeFile} not found".PHP_EOL;

            return;
        }

        $changeSet = require $schemeFile;
        if (!is_array($changeSet))
        {
            throw new \RuntimeException('No change set array found');
        }

        $this->databaseManager->execute($changeSet, true);
    }
}
