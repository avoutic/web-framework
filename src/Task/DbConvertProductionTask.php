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
        return 'Convert production database to migration system';
    }

    public function getUsage(): string
    {
        return <<<'EOF'
        Convert production database to migration system.

        This task will convert the production database to the new migrations system.

        It assumes all scheme files have already been converted to the new migrations system,
        and marks them as already executed.

        Usage:
        framework db:convert-production [--dry-run]
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

        $this->databaseConversionManager->convertProduction($this->dryRun);
    }

    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }
}
