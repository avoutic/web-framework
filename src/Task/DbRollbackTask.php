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
use WebFramework\Database\Database;
use WebFramework\Database\NullDatabase;
use WebFramework\Exception\ArgumentParserException;
use WebFramework\Migration\MigrationManager;

/**
 * Task for rolling back the last batch of database migrations.
 *
 * A rollback destroys data. So it has to be confirmed by typing a text that contains the
 * batch number, on a terminal: it cannot be confirmed with an option or with piped input.
 */
class DbRollbackTask extends ConsoleTask
{
    private bool $dryRun = false;

    /**
     * DbRollbackTask constructor.
     *
     * @param BootstrapService $bootstrapService The bootstrap service
     * @param Database         $database         The database
     * @param MigrationManager $migrationManager The migration manager service
     * @param resource         $outputStream     The output stream
     * @param resource         $inputStream      The input stream the confirmation is read from
     */
    public function __construct(
        private BootstrapService $bootstrapService,
        private Database $database,
        private MigrationManager $migrationManager,
        private $outputStream = STDOUT,
        private $inputStream = STDIN,
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

    public function getCommand(): string
    {
        return 'db:rollback';
    }

    public function getDescription(): string
    {
        return 'Roll back the last batch of database migrations';
    }

    public function getUsage(): string
    {
        return <<<'EOF'
        Roll back the last batch of database migrations.

        The last batch is what the last run of db:migrate executed for one type of
        migrations (framework or app). Its migrations are rolled back newest first,
        using their 'down' actions.

        A rollback destroys data. You have to confirm it by typing a text on the
        terminal. This cannot be done with an option or with piped input.

        Usage:
        framework db:rollback [--dry-run]

        Options:
        --dry-run    Show what would be rolled back (no changes will be made)
        EOF;
    }

    public function getOptions(): array
    {
        return [
            new TaskOption('dry-run', 'd', 'Show what would be rolled back (no changes will be made)', false, [$this, 'setDryRun']),
        ];
    }

    public function setDryRun(): void
    {
        $this->dryRun = true;
    }

    /**
     * Check if the confirmation can be typed by a person.
     */
    protected function isInteractive(): bool
    {
        return stream_isatty($this->inputStream);
    }

    /**
     * Execute the database rollback task.
     *
     * @throws ArgumentParserException If a rollback is requested without a terminal to confirm it on
     * @throws \RuntimeException       If a migration of the batch cannot be rolled back
     */
    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        if ($this->database instanceof NullDatabase)
        {
            $this->write('Called on NullDatabase instance'.PHP_EOL);

            return;
        }

        ['batch' => $batch, 'migrations' => $migrations] = $this->migrationManager->getLastBatch();

        if (!count($migrations))
        {
            $this->write('Nothing to roll back'.PHP_EOL);

            return;
        }

        $this->write("Batch {$batch} contains ".count($migrations).' migration(s), to roll back in this order:'.PHP_EOL);

        $notSupported = [];
        foreach ($migrations as $migration)
        {
            $this->write(" - {$migration['migration']} ({$migration['type']})".PHP_EOL);

            if (!$this->migrationManager->supportsDirection($migration['migration'].'.php', $migration['type'], 'down'))
            {
                $notSupported[] = $migration['migration'];
            }
        }

        // Check everything before the first change, to not end up half way
        //
        if (count($notSupported))
        {
            $this->write(PHP_EOL.'Cannot roll back, no migration file with \'down\' actions for:'.PHP_EOL);
            foreach ($notSupported as $name)
            {
                $this->write(" - {$name}".PHP_EOL);
            }

            throw new \RuntimeException("Batch {$batch} cannot be rolled back: ".implode(', ', $notSupported).' cannot be rolled back');
        }

        if (!$this->dryRun && !$this->confirm($batch))
        {
            $this->write('Not confirmed. Nothing was changed.'.PHP_EOL);

            return;
        }

        $this->write(PHP_EOL);

        foreach ($migrations as $migration)
        {
            $this->migrationManager->runMigration($migration['migration'].'.php', $batch, $migration['type'], 'down', $this->dryRun);
        }

        $this->write($this->dryRun ? 'Dry run finished. Nothing was changed.'.PHP_EOL : "Rolled back batch {$batch}".PHP_EOL);
    }

    /**
     * Ask for the confirmation of the rollback.
     *
     * @throws ArgumentParserException If there is no terminal to confirm on
     */
    private function confirm(int $batch): bool
    {
        if (!$this->isInteractive())
        {
            throw new ArgumentParserException('A rollback has to be confirmed on a terminal. Use --dry-run to see what would be rolled back.');
        }

        $expected = "rollback batch {$batch}";

        $this->write(PHP_EOL.'This destroys data: dropped columns and tables are lost.'.PHP_EOL);
        $this->write("Type \"{$expected}\" to continue: ");

        $typed = fgets($this->inputStream);

        return $typed !== false && trim($typed) === $expected;
    }
}
