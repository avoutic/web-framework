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
use WebFramework\Core\ConfigService;
use WebFramework\Core\Database;
use WebFramework\Core\MigrationManager;

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
     * @param ConfigService    $configService    The configuration service
     * @param LoggerInterface  $logger           The logger
     * @param MigrationManager $migrationManager The migration manager service
     * @param bool             $checkDb          Whether to check the database
     * @param bool             $checkMigrations  Whether to check migration status
     */
    public function __construct(
        private Database $database,
        private ConfigService $configService,
        private LoggerInterface $logger,
        private MigrationManager $migrationManager,
        private bool $checkDb = true,
        private bool $checkMigrations = true,
    ) {}

    /**
     * Check if the supported framework version is configured and the framework version matches the required version.
     *
     * @return bool True if check passes, false otherwise
     */
    private function checkFrameworkVersion(): bool
    {
        $requiredWfVersion = FRAMEWORK_VERSION;
        $supportedWfVersion = $this->configService->get('versions.supported_framework');

        if ($supportedWfVersion == -1)
        {
            $this->logger->emergency('No supported Framework version configured', ['required_wf_version' => $requiredWfVersion, 'supported_wf_version' => $supportedWfVersion]);
            $this->addOutput(
                '   No supported Framework version configured'.PHP_EOL.
                '   There is no supported framework version provided in "versions.supported_framework".'.PHP_EOL.
                "   The current version is {$requiredWfVersion} of this Framework.".PHP_EOL
            );

            return false;
        }

        if ($requiredWfVersion != $supportedWfVersion)
        {
            $this->logger->emergency('Framework version mismatch', ['required_wf_version' => $requiredWfVersion, 'supported_wf_version' => $supportedWfVersion]);
            $this->addOutput(
                '   Framework version mismatch'.PHP_EOL.
                '   Please make sure that this app is upgraded to support version '.PHP_EOL.
                "   {$requiredWfVersion} of this Framework.".PHP_EOL
            );

            return false;
        }

        return true;
    }

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
        $this->addOutput('Checking WF version:'.PHP_EOL);
        if (!$this->checkFrameworkVersion())
        {
            return false;
        }
        $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);

        if ($this->configService->get('database_enabled') != true || !$this->checkDb)
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
