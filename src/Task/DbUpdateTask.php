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

/**
 * Task for updating the database schema.
 */
class DbUpdateTask extends ConsoleTask
{
    private bool $dryRun = false;

    /**
     * DbUpdateTask constructor.
     *
     * @param Container        $container        The dependency injection container
     * @param BootstrapService $bootstrapService The bootstrap service
     * @param DatabaseManager  $databaseManager  The database manager service
     * @param resource         $outputStream     The output stream
     */
    public function __construct(
        private Container $container,
        private BootstrapService $bootstrapService,
        private DatabaseManager $databaseManager,
        private $outputStream = STDOUT
    ) {}

    /**
     * Write a message to the output stream.
     *
     * @param string $message The message to write
     */
    private function write(string $message): void
    {
        fwrite($this->outputStream, $message);
    }

    /**
     * Get the command for the task.
     *
     * @return string The command for the task
     */
    public function getCommand(): string
    {
        return 'db:update';
    }

    public function getDescription(): string
    {
        return 'Update the database to the latest version';
    }

    /**
     * Get the options for the task.
     *
     * @return array<array{long: string, short?: string, description: string, has_value: bool, setter: callable}> The options for the task
     */
    public function getOptions(): array
    {
        return [
            [
                'long' => 'dry-run',
                'short' => 'd',
                'description' => 'Dry run the task (no changes will be made)',
                'has_value' => false,
                'setter' => [$this, 'setDryRun'],
            ],
        ];
    }

    public function setDryRun(bool $dryRun = true): void
    {
        $this->dryRun = $dryRun;
    }

    /**
     * Execute the database update task.
     *
     * This method applies the schema changes defined in the update data.
     */
    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        $currentVersion = $this->databaseManager->getCurrentAppVersion();
        $requiredVersion = $this->databaseManager->getRequiredAppVersion();

        $appDir = $this->container->get('app_dir');

        if ($requiredVersion <= $currentVersion)
        {
            $this->write('Nothing to be done'.PHP_EOL);

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
                    $this->write(" - No changeset for {$nextVersion} available".PHP_EOL);

                    return;
                }
            }

            $changeSet = $this->databaseManager->loadSchemaFile($versionFile);
            $this->databaseManager->execute(
                data: $changeSet,
                dryRun: $this->dryRun,
            );

            if (!$this->dryRun)
            {
                $currentVersion = $this->databaseManager->getCurrentAppVersion();
                $nextVersion = $currentVersion + 1;
            }
            else
            {
                $nextVersion++;
            }
        }
    }
}
