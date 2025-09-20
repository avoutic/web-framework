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
 * Task for showing database migration status.
 */
class DbStatusTask extends ConsoleTask
{
    /**
     * DbStatusTask constructor.
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
        return 'db:status';
    }

    public function getDescription(): string
    {
        return 'Show database migration status';
    }

    public function getUsage(): string
    {
        return <<<'EOF'
        Database status, shows the status of the framework and app migrations

        Usage:
        framework db:status
        EOF;
    }

    /**
     * Execute the database status task.
     */
    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        $status = $this->migrationManager->getMigrationStatus();

        $this->write('Framework Migrations:'.PHP_EOL);
        $this->write('  Executed: '.count($status['framework']['executed']).PHP_EOL);
        $this->write('  Pending: '.count($status['framework']['pending']).PHP_EOL);

        if (!empty($status['framework']['pending']))
        {
            $this->write('  Pending files:'.PHP_EOL);
            foreach ($status['framework']['pending'] as $migration)
            {
                $this->write("    - {$migration}".PHP_EOL);
            }
        }

        $this->write(PHP_EOL.'App Migrations:'.PHP_EOL);
        $this->write('  Executed: '.count($status['app']['executed']).PHP_EOL);
        $this->write('  Pending: '.count($status['app']['pending']).PHP_EOL);

        if (!empty($status['app']['pending']))
        {
            $this->write('  Pending files:'.PHP_EOL);
            foreach ($status['app']['pending'] as $migration)
            {
                $this->write("    - {$migration}".PHP_EOL);
            }
        }
    }

    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }
}
