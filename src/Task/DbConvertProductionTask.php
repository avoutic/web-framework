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
use WebFramework\Core\DatabaseConversionManager;

/**
 * Class DbConvertProductionTask.
 *
 * Specialized task for production conversion setup when db_scheme directory is not available.
 */
class DbConvertProductionTask extends ConsoleTask
{
    private bool $dryRun = false;

    /**
     * DbMigrateProductionTask constructor.
     *
     * @param BootstrapService          $bootstrapService          The bootstrap service
     * @param DatabaseConversionManager $databaseConversionManager The database conversion manager
     */
    public function __construct(
        private BootstrapService $bootstrapService,
        private DatabaseConversionManager $databaseConversionManager,
    ) {}

    public function getCommand(): string
    {
        return 'db:convert-production';
    }

    public function getDescription(): string
    {
        return 'Convert production database to migration system (when db_scheme directory is unavailable)';
    }

    public function getOptions(): array
    {
        return [
            [
                'long' => 'dry-run',
                'short' => 'd',
                'description' => 'Dry run mode',
                'has_value' => false,
                'setter' => [$this, 'setDryRun'],
            ],
        ];
    }

    public function setDryRun(): void
    {
        $this->dryRun = true;
    }

    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        $this->databaseConversionManager->convertProduction($this->dryRun);
    }

    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }

    /**
     * Get the task help text.
     *
     * @return string The task help text
     */
    public function getHelp(): string
    {
        return <<<'HELP'
This command handles Case 3: Active production systems with existing database
but no db_scheme directory and no migrations table yet.

It will:
1. Detect the current database schema version by examining existing tables
2. Register framework migrations as "already applied" in the migrations table
3. Register legacy migrations based on detected schema version
4. Prepare the system for applying new migrations going forward

This is different from other migration commands:
- Case 1 (dev with db_scheme): Use 'php Framework db:convert-from-scheme'
- Case 2 (new dev/production): Use 'php Framework db:migrate'
- Case 3 (existing production): Use this command
- Case 4 (new production): Use 'php Framework db:migrate'

Usage:
  php Framework db:convert-production [--dry-run]

Options:
  --dry-run, -d    Show what would be done without making changes

After running this command:
1. Verify the migration status with: php Framework db:status
2. Apply any pending migrations with: php Framework db:migrate
3. Monitor your application logs for any issues

This command is idempotent - it's safe to run multiple times.
HELP;
    }
}
