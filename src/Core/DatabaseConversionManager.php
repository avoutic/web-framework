<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;

/**
 * Class DatabaseConversionManager.
 *
 * Manages database conversion for both framework and application code.
 */
class DatabaseConversionManager
{
    /**
     * DatabaseConversionManager constructor.
     *
     * @param Container        $container        The dependency injection container
     * @param Database         $database         The database interface implementation
     * @param MigrationManager $migrationManager The migration manager
     * @param resource         $outputStream     The output stream to write to
     */
    public function __construct(
        private Container $container,
        private Database $database,
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
     * Convert from old db_scheme system to new migrations system.
     *
     * It converts old scheme files to migration files and registers them as executed.
     *
     * @param bool $dryRun Whether to dry run the migration
     */
    public function convertFromDbScheme(bool $dryRun = false): void
    {
        if ($this->database instanceof NullDatabase)
        {
            $this->write('Called on NullDatabase instance'.PHP_EOL);

            return;
        }

        $appDir = $this->container->get('app_dir');
        $dbSchemeDir = "{$appDir}/db_scheme";

        if (!is_dir($dbSchemeDir))
        {
            $this->write("No db_scheme directory found at {$dbSchemeDir}".PHP_EOL);
            $this->write('This command is for converting scheme files to migrations.'.PHP_EOL);
            $this->write('To convert production systems, use: php Framework db:convert-production'.PHP_EOL);
            $this->write('To migrate on other systems, use: php Framework db:migrate'.PHP_EOL);

            return;
        }

        try
        {
            $result = $this->database->query('SELECT value FROM stored_values WHERE module = ? AND name = ?', ['db', 'app_db_version']);
            if ($result->RecordCount() === 0)
            {
                $this->write('No app_db_version found in stored_values table'.PHP_EOL);
                $this->write('No previously initialized database found.'.PHP_EOL);

                return;
            }

            $currentVersion = (int) $result->fields['value'];
        }
        catch (\RuntimeException $e)
        {
            $result = $this->database->query('SELECT value FROM config_values WHERE module = ? AND name = ?', ['db', 'app_db_version']);
            $currentVersion = (int) $result->fields['value'];
        }

        $this->write("Current app database version: {$currentVersion}".PHP_EOL);

        $allFrameworkMigrations = $this->migrationManager->getPendingMigrations('framework');
        $frameworkMigrations = array_filter($allFrameworkMigrations, function ($migration) {
            return str_starts_with($migration, '0000_01_01_');
        });

        if (!count($allFrameworkMigrations) && $currentVersion === 0)
        {
            $this->write('No migrations to register (app_db_version is 0 and no required framework migrations found).'.PHP_EOL);

            return;
        }

        $migrationsDir = "{$appDir}/migrations";

        $numericMigrations = [];
        for ($i = 1; $i <= $currentVersion; $i++)
        {
            if (file_exists("{$dbSchemeDir}/{$i}.php"))
            {
                $numericMigrations[] = $i;
            }
            elseif (file_exists("{$dbSchemeDir}/{$i}.inc.php"))
            {
                $numericMigrations[] = $i;
            }
            else
            {
                $this->write("Warning: Migration file {$i}.php or {$i}.inc.php not found but version {$currentVersion} indicates it should exist.".PHP_EOL);
            }

            $migrationName = $this->getLegacyMigrationName($i);
            $migrationFile = "{$migrationsDir}/{$migrationName}.php";

            if (file_exists($migrationFile))
            {
                $this->write("Migration file {$migrationFile} already exists, project already converted".PHP_EOL);

                return;
            }
        }

        $this->write('Found '.count($numericMigrations).' numeric scheme files to convert.'.PHP_EOL);

        if (!$dryRun)
        {
            $this->migrationManager->ensureMigrationsTable();

            if (!is_dir($migrationsDir))
            {
                mkdir($migrationsDir, 0o755, true);
                $this->write("Created migrations directory: {$migrationsDir}".PHP_EOL);
            }

            $batch = $this->migrationManager->getNextBatchNumber();

            foreach ($frameworkMigrations as $migration)
            {
                $migrationName = pathinfo($migration, PATHINFO_FILENAME);
                $this->write("Registering framework migration: {$migrationName}".PHP_EOL);
                $this->migrationManager->recordMigration($migrationName, 'framework', $batch);
            }

            foreach ($numericMigrations as $version)
            {
                $migrationName = $this->getLegacyMigrationName($version);

                $this->convertSchemeFileToMigration($version, $dbSchemeDir, $migrationsDir, $migrationName);

                $this->write("Registering converted migration: {$migrationName}".PHP_EOL);
                $this->migrationManager->recordMigration($migrationName, 'app', $batch);
            }

            $this->write('Successfully converted and registered '.count($numericMigrations).' legacy migrations.'.PHP_EOL);
            $this->write(''.PHP_EOL);
            $this->write('Migration conversion complete! Next steps:'.PHP_EOL);
            $this->write('1. Remove your db_scheme directory: rm -rf db_scheme'.PHP_EOL);
            $this->write("2. Remove 'versions.required_app_db' from your config.php".PHP_EOL);
            $this->write('3. From now on, generate new migrations using: php framework db:make migration_name'.PHP_EOL);
            $this->write('4. New and converted systems can now use: php framework db:migrate'.PHP_EOL); // TODO: add this to the help text
            $this->write('5. Production systems can convert with: php framework db:convert-production'.PHP_EOL);
        }
        else
        {
            $this->write('DRY RUN - Would register the following framework migrations:'.PHP_EOL);
            foreach ($frameworkMigrations as $migration)
            {
                $this->write("  - {$migration}".PHP_EOL);
            }

            $this->write('DRY RUN - Would register the following legacy migrations:'.PHP_EOL);
            foreach ($numericMigrations as $version)
            {
                $this->write("  - legacy_db_scheme_{$version}".PHP_EOL);
            }
        }
    }

    /**
     * Handle conversion for production environments.
     *
     * This method detects existing database schema and registers appropriate migrations
     * as "already applied" to prevent re-application on production systems.
     *
     * @param bool $dryRun Whether to dry run the migration
     */
    private function handleProductionConversion(bool $dryRun = false): void
    {
        $this->write('Production migration detection starting...'.PHP_EOL);

        $currentVersion = $this->detectCurrentSchemaVersion();
        $this->write("Detected schema version: {$currentVersion}".PHP_EOL);

        $allFrameworkMigrations = $this->migrationManager->getPendingMigrations('framework');
        $frameworkMigrations = array_filter($allFrameworkMigrations, function ($migration) {
            return str_starts_with($migration, '0000_01_01_');
        });

        if (!count($allFrameworkMigrations) && $currentVersion === 0)
        {
            $this->write('No migrations to register (detected version is 0 and no required framework migrations found).'.PHP_EOL);

            return;
        }

        // Check if all legacy migrations are present
        $appDir = $this->container->get('app_dir');
        $migrationsDir = "{$appDir}/migrations";
        $legacyMigrations = [];
        for ($i = 1; $i <= $currentVersion; $i++)
        {
            $migrationFile = $this->getLegacyMigrationName($i);
            if (!file_exists("{$migrationsDir}/{$migrationFile}.php"))
            {
                $this->write("Error: Legacy migration file {$migrationFile} not found, this should not happen in production".PHP_EOL);

                return;
            }

            $legacyMigrations[] = $migrationFile;
        }

        $this->write('Found '.count($frameworkMigrations).' framework migrations to register.'.PHP_EOL);
        $this->write("Found {$currentVersion} legacy schema versions to register.".PHP_EOL);

        if (!$dryRun)
        {
            $this->migrationManager->ensureMigrationsTable();
            $batch = $this->migrationManager->getNextBatchNumber();

            foreach ($frameworkMigrations as $migration)
            {
                $migrationName = pathinfo($migration, PATHINFO_FILENAME);
                $this->write("Registering framework migration: {$migrationName}".PHP_EOL);
                $this->migrationManager->recordMigration($migrationName, 'framework', $batch);
            }

            foreach ($legacyMigrations as $migration)
            {
                $this->write("Registering legacy migration: {$migration}".PHP_EOL);
                $this->migrationManager->recordMigration($migration, 'app', $batch);
            }

            $this->write('Successfully registered '.($currentVersion + count($frameworkMigrations)).' migrations for production environment.'.PHP_EOL);
            $this->write(''.PHP_EOL);
            $this->write('Production migration registration complete.'.PHP_EOL);
            $this->write('You can now run "php Framework db:migrate" to apply any new migrations.'.PHP_EOL);
        }
        else
        {
            $this->write('DRY RUN - Would register the following framework migrations:'.PHP_EOL);
            foreach ($frameworkMigrations as $migration)
            {
                $this->write("  - {$migration}".PHP_EOL);
            }

            $this->write('DRY RUN - Would register the following legacy migrations:'.PHP_EOL);
            foreach ($legacyMigrations as $migration)
            {
                $this->write("  - {$migration}".PHP_EOL);
            }
        }
    }

    /**
     * Detect the current schema version by examining the database state.
     *
     * This method attempts to determine what version of the old db_scheme system
     * was last applied by checking stored values and database schema.
     *
     * @return int The detected schema version
     *
     * @throws \RuntimeException If no schema version is detected
     */
    private function detectCurrentSchemaVersion(): int
    {
        try
        {
            if ($this->database->tableExists('stored_values'))
            {
                $result = $this->database->query('SELECT value FROM stored_values WHERE module = ? AND name = ?', ['db', 'app_db_version']);
                if (isset($result->fields['value']))
                {
                    $version = (int) $result->fields['value'];
                    $this->write("Found app_db_version in stored_values: {$version}".PHP_EOL);

                    return $version;
                }
            }
        }
        catch (\RuntimeException $e)
        {
            $this->write("Could not read from stored_values table: {$e->getMessage()}".PHP_EOL);
        }

        try
        {
            if ($this->database->tableExists('config_values'))
            {
                $result = $this->database->query('SELECT value FROM config_values WHERE module = ? AND name = ?', ['db', 'app_db_version']);
                if (isset($result->fields['value']))
                {
                    $version = (int) $result->fields['value'];
                    $this->write("Found app_db_version in config_values: {$version}".PHP_EOL);

                    return $version;
                }
            }
        }
        catch (\RuntimeException $e)
        {
            $this->write("Could not read from config_values table: {$e->getMessage()}".PHP_EOL);
        }

        throw new \RuntimeException('No schema version detected');
    }

    /**
     * Create a production-safe migration command for deployment scenarios.
     *
     * This method provides a specialized migration path for production deployments
     * where the current version is based on the old scheme system.
     *
     * @param bool $dryRun Whether to dry run the migration
     */
    public function convertProduction(bool $dryRun = false): void
    {
        $this->write('Starting production migration setup...'.PHP_EOL);

        if ($this->database->tableExists('migrations'))
        {
            $this->write('Migrations table already exists - checking migration status...'.PHP_EOL);
            $status = $this->migrationManager->getMigrationStatus();

            $frameworkExecuted = count($status['framework']['executed']);
            $appExecuted = count($status['app']['executed']);

            if ($frameworkExecuted > 0 || $appExecuted > 0)
            {
                $this->write("Migration system already initialized ({$frameworkExecuted} framework, {$appExecuted} app migrations recorded)".PHP_EOL);
                $this->write('Use "php Framework db:migrate" to apply any pending migrations.'.PHP_EOL);

                return;
            }
        }

        $this->write('No existing migration history found - performing schema-based detection...'.PHP_EOL);
        $this->handleProductionConversion($dryRun);
    }

    /**
     * Convert a numeric db_scheme file to a timestamp-based migration file.
     *
     * @param int    $version       The scheme version number
     * @param string $dbSchemeDir   The db_scheme directory path
     * @param string $migrationsDir The migrations directory path
     * @param string $migrationName The name of the migration
     */
    private function convertSchemeFileToMigration(int $version, string $dbSchemeDir, string $migrationsDir, string $migrationName): void
    {
        $schemeFile = "{$dbSchemeDir}/{$version}.php";
        $schemeIncFile = "{$dbSchemeDir}/{$version}.inc.php";

        if (!file_exists($schemeFile) && !file_exists($schemeIncFile))
        {
            $this->write("Warning: Scheme file {$version}.php or {$version}.inc.php not found, creating placeholder migration".PHP_EOL);
            $this->createPlaceholderMigration($version, $migrationsDir);

            return;
        }

        $schemeData = [];
        if (file_exists($schemeFile))
        {
            $schemeData = require $schemeFile;
        }
        elseif (file_exists($schemeIncFile))
        {
            $schemeData = require $schemeIncFile;
        }

        $migrationFile = "{$migrationsDir}/{$migrationName}.php";

        if (file_exists($migrationFile))
        {
            $this->write("Migration file {$migrationFile} already exists, skipping".PHP_EOL);

            return;
        }

        $migrationData = [
            'up' => [
                'actions' => $schemeData['actions'] ?? [],
            ],
            'down' => [
                'actions' => [],
            ],
        ];

        $migrationContent = "<?php\n\nreturn ".var_export($migrationData, true).";\n";

        file_put_contents($migrationFile, $migrationContent);
        $this->write("Converted {$version}.php to {$migrationName}.php".PHP_EOL);
    }

    /**
     * Create a placeholder migration for missing scheme files.
     *
     * @param int    $version       The scheme version number
     * @param string $migrationsDir The migrations directory path
     */
    private function createPlaceholderMigration(int $version, string $migrationsDir): void
    {
        $migrationName = $this->getLegacyMigrationName($version);
        $migrationFile = "{$migrationsDir}/{$migrationName}.php";

        $migrationData = [
            'up' => [
                'actions' => [],
            ],
            'down' => [
                'actions' => [],
            ],
        ];

        $migrationContent = "<?php\n\n// Placeholder for missing db_scheme/{$version}.php\nreturn ".var_export($migrationData, true).";\n";

        file_put_contents($migrationFile, $migrationContent);
    }

    /**
     * Generate a consistent migration name for legacy scheme files.
     *
     * @param int $version The scheme version number
     *
     * @return string The migration filename (without .php extension)
     */
    private function getLegacyMigrationName(int $version): string
    {
        $paddedVersion = str_pad((string) $version, 4, '0', STR_PAD_LEFT);

        return "0000_01_01_000000_legacy_db_scheme_{$paddedVersion}";
    }
}
