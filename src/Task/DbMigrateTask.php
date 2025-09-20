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
use WebFramework\Migration\MigrationManager;

/**
 * Task for running database migrations using the new timestamp-based system.
 */
class DbMigrateTask extends ConsoleTask
{
    private bool $dryRun = false;
    private bool $frameworkOnly = false;

    /**
     * DbMigrateTask constructor.
     *
     * @param BootstrapService $bootstrapService The bootstrap service
     * @param MigrationManager $migrationManager The migration manager service
     * @param resource         $outputStream     The output stream
     */
    public function __construct(
        private BootstrapService $bootstrapService,
        private MigrationManager $migrationManager,
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
        return 'db:migrate';
    }

    public function getDescription(): string
    {
        return 'Run pending database migrations';
    }

    public function getUsage(): string
    {
        return <<<'EOF'
        Run pending database migrations.

        App migrations are discovered in the /migrations directory of the app,
        and are run after the framework migrations, in alphabetical order.

        Usage:
        framework db:migrate [--dry-run] [--framework]

        Options:
        --dry-run    Dry run the task (no changes will be made)
        --framework  Run framework migrations only
        EOF;
    }

    public function getOptions(): array
    {
        return [
            new TaskOption('dry-run', 'd', 'Dry run the task (no changes will be made)', false, [$this, 'setDryRun']),
            new TaskOption('framework', 'f', 'Run framework migrations only', false, [$this, 'setFrameworkOnly']),
        ];
    }

    public function setDryRun(): void
    {
        $this->dryRun = true;
    }

    public function setFrameworkOnly(): void
    {
        $this->frameworkOnly = true;
    }

    /**
     * Execute the database migration task.
     */
    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        if ($this->frameworkOnly)
        {
            $this->write('Running framework migrations...'.PHP_EOL);
            $this->migrationManager->runPendingMigrations('framework', $this->dryRun);
        }
        else
        {
            $this->write('Running framework migrations...'.PHP_EOL);
            $this->migrationManager->runPendingMigrations('framework', $this->dryRun);

            $this->write('Running app migrations...'.PHP_EOL);
            $this->migrationManager->runPendingMigrations('app', $this->dryRun);
        }

        $this->write('Migration complete.'.PHP_EOL);
    }

    /**
     * Check if the task handles its own bootstrapping.
     *
     * @return bool True if the task handles its own bootstrapping, false otherwise
     */
    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }
}
