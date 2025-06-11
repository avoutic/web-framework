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
 * Class DbConvertFromSchemeTask.
 *
 * Converts from old db_scheme system to new migrations system.
 */
class DbConvertFromSchemeTask extends ConsoleTask
{
    private bool $dryRun = false;

    /**
     * DbMigrateFromSchemeTask constructor.
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
        return 'db:convert-from-scheme';
    }

    public function getDescription(): string
    {
        return 'Migrate from old db_scheme system to new migrations system';
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

        $this->databaseConversionManager->convertFromDbScheme($this->dryRun);
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
This command helps convert existing applications from the old numeric db_scheme
migration system to the new timestamp-based migrations system.

It will:
1. Read the current app_db_version from stored_values table
2. Register all applied numeric migrations (1.php through current version)
   as "already applied" in the new migrations table
3. Provide instructions for completing the transition

Usage:
  php Framework db:migrate-from-scheme [--dry-run]

Options:
  --dry-run, -d    Show what would be done without making changes

After running this command:
1. Create a 'migrations' directory in your app
2. Generate new migrations using 'php Framework db:make migration_name'
3. Consider backing up your db_scheme directory
4. Remove 'versions.required_app_db' from your config.php

For production deployments where db_scheme directory is not available:
Use 'php Framework db:convert-production' instead of this command.
HELP;
    }
}
