<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\SanityCheck;

use Psr\Log\LoggerInterface;
use WebFramework\Database\Database;
use WebFramework\Database\NullDatabase;
use WebFramework\Migration\MigrationManager;

/**
 * Class DatabaseCompatibility.
 *
 * Performs sanity checks related to database compatibility.
 */
class DatabaseCompatibility extends Base
{
    /**
     * DatabaseCompatibility constructor.
     *
     * @param Database         $database         The database service
     * @param LoggerInterface  $logger           The logger
     * @param MigrationManager $migrationManager The migration manager service
     * @param bool             $checkDb          Whether to check the database
     * @param bool             $checkMigrations  Whether to check migration status
     */
    public function __construct(
        private Database $database,
        private LoggerInterface $logger,
        private MigrationManager $migrationManager,
        private bool $checkDb = true,
        private bool $checkMigrations = true,
    ) {}

    /**
     * Check if the stored_values table exists.
     *
     * @return bool True if check passes, false otherwise
     */
    private function checkStoredValuesTable(): bool
    {
        if (!$this->database->tableExists('stored_values'))
        {
            $this->logger->emergency('Database missing stored_values table');
            $this->addOutput(
                '   Database missing stored_values table'.PHP_EOL.
                '   Please run "php Framework db:migrate" to set up the database schema.'.PHP_EOL
            );

            return false;
        }

        return true;
    }

    /**
     * Check if migrations table exists and is properly set up.
     *
     * @return bool True if check passes, false otherwise
     */
    private function checkMigrationsTable(): bool
    {
        if (!$this->database->tableExists('migrations'))
        {
            $this->logger->emergency('Database missing migrations table');
            $this->addOutput(
                '   Database missing migrations table'.PHP_EOL.
                '   Please run "php Framework db:migrate" to set up the migration system.'.PHP_EOL
            );

            return false;
        }

        return true;
    }

    /**
     * Check if there are pending migrations that need to be applied.
     *
     * @return bool True if check passes, false otherwise
     */
    private function checkPendingMigrations(): bool
    {
        if (!$this->checkMigrations)
        {
            return true;
        }

        try
        {
            $status = $this->migrationManager->getMigrationStatus();

            $frameworkPending = count($status['framework']['pending']);
            $appPending = count($status['app']['pending']);

            if ($frameworkPending > 0)
            {
                $this->logger->emergency('Pending framework migrations', ['pending_count' => $frameworkPending]);
                $this->addOutput(
                    '   Pending framework migrations detected'.PHP_EOL.
                    "   {$frameworkPending} framework migration(s) need to be applied.".PHP_EOL.
                    '   Please run "php Framework db:migrate --framework" to apply them.'.PHP_EOL
                );

                return false;
            }

            if ($appPending > 0)
            {
                $this->logger->emergency('Pending app migrations', ['pending_count' => $appPending]);
                $this->addOutput(
                    '   Pending app migrations detected'.PHP_EOL.
                    "   {$appPending} app migration(s) need to be applied.".PHP_EOL.
                    '   Please run "php Framework db:migrate" to apply them.'.PHP_EOL
                );

                return false;
            }
        }
        catch (\Exception $e)
        {
            $this->logger->emergency('Migration status check failed', ['error' => $e->getMessage()]);
            $this->addOutput(
                '   Migration status check failed'.PHP_EOL.
                '   Error: '.$e->getMessage().PHP_EOL
            );

            return false;
        }

        return true;
    }

    /**
     * Perform database compatibility checks.
     *
     * @return bool True if all checks pass, false otherwise
     */
    public function performChecks(): bool
    {
        if (!$this->database instanceof NullDatabase || !$this->checkDb)
        {
            return true;
        }

        $this->addOutput('Checking for stored_values table:'.PHP_EOL);
        if (!$this->checkStoredValuesTable())
        {
            return false;
        }
        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        $this->addOutput('Checking for migrations table:'.PHP_EOL);
        if (!$this->checkMigrationsTable())
        {
            return false;
        }
        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        $this->addOutput('Checking for pending migrations:'.PHP_EOL);
        if (!$this->checkPendingMigrations())
        {
            return false;
        }
        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        return true;
    }
}
