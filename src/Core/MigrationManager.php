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
 * Class MigrationManager.
 *
 * Manages timestamp-based database migrations for both framework and application code.
 */
class MigrationManager
{
    /**
     * MigrationManager constructor.
     *
     * @param Container       $container       The dependency injection container
     * @param Database        $database        The database interface implementation
     * @param DatabaseManager $databaseManager The database manager
     * @param resource        $outputStream    The output stream to write to
     */
    public function __construct(
        private Container $container,
        private Database $database,
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
     * Check if the migrations table exists and create it if not.
     */
    public function ensureMigrationsTable(): void
    {
        if (!$this->database->tableExists('migrations'))
        {
            $this->write('Creating migrations table...'.PHP_EOL);

            $createTableData = [
                'actions' => [
                    [
                        'type' => 'create_table',
                        'table_name' => 'migrations',
                        'fields' => [
                            [
                                'name' => 'migration',
                                'type' => 'varchar',
                                'size' => 255,
                            ],
                            [
                                'name' => 'type',
                                'type' => 'varchar',
                                'size' => 20,
                                'default' => 'app',
                            ],
                            [
                                'name' => 'batch',
                                'type' => 'int',
                                'default' => 1,
                            ],
                            [
                                'name' => 'executed_at',
                                'type' => 'timestamp',
                                'default' => ['function' => 'CURRENT_TIMESTAMP'],
                            ],
                        ],
                        'constraints' => [
                            [
                                'type' => 'unique',
                                'values' => ['migration', 'type'],
                            ],
                        ],
                    ],
                ],
            ];

            $this->databaseManager->execute($createTableData);
        }
    }

    /**
     * Discover migration files in a directory.
     *
     * @param string $directory The directory to scan
     *
     * @return array<string> Array of migration filenames sorted by timestamp
     */
    public function discoverMigrations(string $directory): array
    {
        if (!is_dir($directory))
        {
            return [];
        }

        $migrations = [];
        $files = scandir($directory);

        foreach ($files as $file)
        {
            if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_(.+)\.php$/', $file, $matches))
            {
                $migrations[] = $file;
            }
        }

        sort($migrations);

        return $migrations;
    }

    /**
     * Get migrations that have been executed.
     *
     * @param string $type Migration type ('app' or 'framework')
     *
     * @return array<string> Array of executed migration names
     */
    public function getExecutedMigrations(string $type = 'app'): array
    {
        $this->ensureMigrationsTable();

        $query = 'SELECT migration FROM migrations WHERE type = ? ORDER BY migration';
        $result = $this->database->query($query, [$type]);

        $executed = [];
        foreach ($result as $row)
        {
            $executed[] = $row['migration'];
        }

        return $executed;
    }

    /**
     * Get pending migrations that need to be executed.
     *
     * @param string $type Migration type ('app' or 'framework')
     *
     * @return array<string> Array of pending migration filenames
     */
    public function getPendingMigrations(string $type = 'app'): array
    {
        if ($type === 'framework')
        {
            $directory = dirname(__DIR__, 2).'/migrations';
        }
        else
        {
            $appDir = $this->container->get('app_dir');
            $directory = "{$appDir}/migrations";
        }

        $allMigrations = $this->discoverMigrations($directory);
        $executedMigrations = $this->getExecutedMigrations($type);

        $pending = [];
        foreach ($allMigrations as $migration)
        {
            $migrationName = pathinfo($migration, PATHINFO_FILENAME);
            if (!in_array($migrationName, $executedMigrations))
            {
                $pending[] = $migration;
            }
        }

        return $pending;
    }

    /**
     * Run a single migration file.
     *
     * @param string $migrationFile The migration filename
     * @param int    $batch         Batch number
     * @param string $type          Migration type ('app' or 'framework')
     * @param string $direction     Migration direction ('up' or 'down')
     * @param bool   $dryRun        Whether to dry run the migration
     */
    public function runMigration(string $migrationFile, int $batch, string $type = 'app', string $direction = 'up', bool $dryRun = false): void
    {
        if ($type === 'framework')
        {
            $directory = dirname(__DIR__, 2).'/migrations';
        }
        else
        {
            $appDir = $this->container->get('app_dir');
            $directory = "{$appDir}/migrations";
        }

        $migrationPath = "{$directory}/{$migrationFile}";

        if (!file_exists($migrationPath))
        {
            throw new \InvalidArgumentException("Migration file {$migrationPath} not found");
        }

        $this->write("Running {$type} migration: {$migrationFile} ({$direction})".PHP_EOL);

        $migrationData = require $migrationPath;

        if (isset($migrationData[$direction]) && is_array($migrationData[$direction]))
        {
            $actions = $migrationData[$direction];
        }
        elseif ($direction === 'up' && isset($migrationData['actions']) && is_array($migrationData['actions']))
        {
            $actions = ['actions' => $migrationData['actions']];
        }
        else
        {
            throw new \InvalidArgumentException("Migration {$migrationFile} does not support {$direction} direction");
        }

        if (!isset($actions['actions']) || !is_array($actions['actions']))
        {
            throw new \InvalidArgumentException("Migration {$migrationFile} has invalid actions format");
        }

        if (!$dryRun)
        {
            $this->databaseManager->execute($actions);

            $migrationName = pathinfo($migrationFile, PATHINFO_FILENAME);

            if ($direction === 'up')
            {
                $this->recordMigration($migrationName, $type, $batch);
            }
            else
            {
                $this->removeMigrationRecord($migrationName, $type);
            }
        }
        else
        {
            $this->databaseManager->execute($actions, true);
        }
    }

    /**
     * Record a migration as executed.
     *
     * @param string $migrationName The migration name
     * @param string $type          Migration type ('app' or 'framework')
     * @param int    $batch         Batch number
     */
    public function recordMigration(string $migrationName, string $type, int $batch): void
    {
        $query = 'INSERT INTO migrations (migration, type, batch) VALUES (?, ?, ?)';
        $this->database->insertQuery($query, [$migrationName, $type, $batch]);
    }

    /**
     * Remove a migration record.
     *
     * @param string $migrationName The migration name
     * @param string $type          Migration type ('app' or 'framework')
     */
    public function removeMigrationRecord(string $migrationName, string $type): void
    {
        $query = 'DELETE FROM migrations WHERE migration = ? AND type = ?';
        $this->database->query($query, [$migrationName, $type]);
    }

    /**
     * Get the next batch number for migrations.
     *
     * @return int The next batch number
     */
    public function getNextBatchNumber(): int
    {
        $query = 'SELECT MAX(batch) as max_batch FROM migrations';
        $result = $this->database->query($query, []);

        $maxBatch = $result->fields['max_batch'] ?? 0;

        return $maxBatch + 1;
    }

    /**
     * Run all pending migrations.
     *
     * @param string $type   Migration type ('app' or 'framework')
     * @param bool   $dryRun Whether to dry run the migrations
     */
    public function runPendingMigrations(string $type = 'app', bool $dryRun = false): void
    {
        $pending = $this->getPendingMigrations($type);

        if (empty($pending))
        {
            $this->write("No pending {$type} migrations".PHP_EOL);

            return;
        }

        $this->write('Found '.count($pending)." pending {$type} migrations".PHP_EOL);

        $batch = $this->getNextBatchNumber();

        foreach ($pending as $migration)
        {
            $this->runMigration($migration, $batch, $type, 'up', $dryRun);
        }
    }

    /**
     * Get migration status for both app and framework migrations.
     *
     * @return array<string, array<string, mixed>> Migration status information
     */
    public function getMigrationStatus(): array
    {
        return [
            'app' => [
                'executed' => $this->getExecutedMigrations('app'),
                'pending' => $this->getPendingMigrations('app'),
            ],
            'framework' => [
                'executed' => $this->getExecutedMigrations('framework'),
                'pending' => $this->getPendingMigrations('framework'),
            ],
        ];
    }

    /**
     * Generate a new migration file with timestamp-based name.
     *
     * @param string $name Migration description
     * @param string $type Migration type ('app' or 'framework')
     *
     * @return string The generated filename
     */
    public function generateMigrationFile(string $name, string $type = 'app'): string
    {
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";

        if ($type === 'framework')
        {
            $directory = dirname(__DIR__, 2).'/migrations';
        }
        else
        {
            $appDir = $this->container->get('app_dir');
            $directory = "{$appDir}/migrations";
        }

        if (!is_dir($directory))
        {
            mkdir($directory, 0o755, true);
        }

        $filepath = "{$directory}/{$filename}";

        $template = <<<'PHP'
<?php

return [
    'up' => [
        'actions' => [
        ],
    ],
    'down' => [
        'actions' => [
        ],
    ],
];
PHP;

        file_put_contents($filepath, $template);

        return $filename;
    }
}
