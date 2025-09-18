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

    public function getUsage(): string
    {
        return <<<'EOF'
        Migrate from old db_scheme system to new migrations system.

        This task will convert the database schema from the old db_scheme system to the new migrations system.

        It will create a new migration file for each old scheme file.

        The migration files will be created in the /migrations directory of the app,
        and will be named like: YYYYMMDDHHMMSS_<name>.php

        The migrations will be marked as already executed.

        Usage:
        framework db:convert-from-scheme [--dry-run]
        EOF;
    }

    public function getOptions(): array
    {
        return [
            new TaskOption('dry-run', 'd', 'Dry run mode', false, [$this, 'setDryRun']),
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
}
