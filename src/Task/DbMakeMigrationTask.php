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
use WebFramework\Core\MigrationManager;

/**
 * Task for generating new migration files.
 */
class DbMakeMigrationTask extends ConsoleTask
{
    private string $migrationName = '';
    private bool $framework = false;

    /**
     * DbMakeMigrationTask constructor.
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
        return 'db:make';
    }

    public function getDescription(): string
    {
        return 'Generate a new migration file';
    }

    /**
     * Get the options for the task.
     *
     * @return array<array{long: string, short?: string, description: string, has_value: bool, setter: callable}> The options for the task
     */
    public function getOptions(): array
    {
        return [
            [
                'long' => 'framework',
                'short' => 'f',
                'description' => 'Create a framework migration',
                'has_value' => false,
                'setter' => [$this, 'setFramework'],
            ],
        ];
    }

    public function setFramework(bool $framework = true): void
    {
        $this->framework = $framework;
    }

    /**
     * Get the arguments for the task.
     *
     * @return array<array{name: string, description: string, required: bool, setter: callable}> The arguments for the task
     */
    public function getArguments(): array
    {
        return [
            [
                'name' => 'name',
                'description' => 'The name/description of the migration',
                'required' => true,
                'setter' => [$this, 'setMigrationName'],
            ],
        ];
    }

    public function setMigrationName(string $name): void
    {
        $this->migrationName = $name;
    }

    /**
     * Execute the make migration task.
     */
    public function execute(): void
    {
        if (empty($this->migrationName))
        {
            $this->write('Error: Migration name is required'.PHP_EOL);

            return;
        }

        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        $type = $this->framework ? 'framework' : 'app';
        $filename = $this->migrationManager->generateMigrationFile($this->migrationName, $type);

        $this->write("Created {$type} migration: {$filename}".PHP_EOL);
    }
}
