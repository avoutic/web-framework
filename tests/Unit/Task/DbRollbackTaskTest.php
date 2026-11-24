<?php

namespace Tests\Unit\Task;

use Codeception\Test\Unit;
use WebFramework\Core\BootstrapService;
use WebFramework\Database\Database;
use WebFramework\Exception\ArgumentParserException;
use WebFramework\Migration\MigrationManager;
use WebFramework\Task\DbRollbackTask;

/**
 * @internal
 *
 * @covers \WebFramework\Task\DbRollbackTask
 */
final class DbRollbackTaskTest extends Unit
{
    /** @var resource */
    private $output;

    /** @var array<array{string, string, bool}> */
    public array $ran = [];

    /**
     * @param array<array{migration: string, type: string}> $migrations
     * @param array<string>                                 $withoutDown
     */
    private function buildTask(array $migrations, string $typed, bool $interactive = true, array $withoutDown = []): DbRollbackTask
    {
        $this->output = fopen('php://memory', 'w+');
        $this->ran = [];

        $input = fopen('php://memory', 'w+');
        fwrite($input, $typed);
        rewind($input);

        $migrationManager = $this->makeEmpty(MigrationManager::class, [
            'getLastBatch' => ['batch' => 5, 'migrations' => $migrations],
            'supportsDirection' => fn (string $file, string $type, string $direction) => !in_array($file, $withoutDown, true),
            'runMigration' => function (string $file, int $batch, string $type, string $direction, bool $dryRun) {
                verify($batch)->equals(5);
                verify($direction)->equals('down');
                $this->ran[] = [$file, $type, $dryRun];
            },
        ]);

        return new class($this->makeEmpty(BootstrapService::class), $this->makeEmpty(Database::class), $migrationManager, $this->output, $input, $interactive) extends DbRollbackTask {
            public function __construct($bootstrapService, $database, $migrationManager, $outputStream, $inputStream, private bool $interactive)
            {
                parent::__construct($bootstrapService, $database, $migrationManager, $outputStream, $inputStream);
            }

            protected function isInteractive(): bool
            {
                return $this->interactive;
            }
        };
    }

    private function getOutput(): string
    {
        rewind($this->output);

        return (string) stream_get_contents($this->output);
    }

    /**
     * @return array<array{migration: string, type: string}>
     */
    private function batch(): array
    {
        // Newest first, as the migration manager returns them
        return [
            ['migration' => '2026_02_01_000002_add_owner', 'type' => 'app'],
            ['migration' => '2026_02_01_000001_create_forms', 'type' => 'app'],
        ];
    }

    public function testRollsBackTheLastBatchNewestFirstAfterTypedConfirmation()
    {
        $task = $this->buildTask($this->batch(), "rollback batch 5\n");

        $task->execute();

        verify($this->ran)->same([
            ['2026_02_01_000002_add_owner.php', 'app', false],
            ['2026_02_01_000001_create_forms.php', 'app', false],
        ]);
        verify($this->getOutput())->stringContainsString('2026_02_01_000002_add_owner');
        verify($this->getOutput())->stringContainsString('rollback batch 5');
    }

    public function testWrongConfirmationChangesNothing()
    {
        $task = $this->buildTask($this->batch(), "yes\n");

        $task->execute();

        verify($this->ran)->same([]);
        verify($this->getOutput())->stringContainsString('Nothing was changed');
    }

    public function testRefusesToRunWithoutATerminal()
    {
        // The right text, but piped in by a script
        $task = $this->buildTask($this->batch(), "rollback batch 5\n", false);

        verify(function () use ($task) {
            $task->execute();
        })->callableThrows(ArgumentParserException::class);

        verify($this->ran)->same([]);
    }

    public function testDryRunNeedsNoConfirmationAndChangesNothing()
    {
        $task = $this->buildTask($this->batch(), '', false);
        $task->setDryRun();

        $task->execute();

        verify($this->ran)->same([
            ['2026_02_01_000002_add_owner.php', 'app', true],
            ['2026_02_01_000001_create_forms.php', 'app', true],
        ]);
    }

    public function testRefusesBeforeRunningAnythingWhenAMigrationHasNoDownBlock()
    {
        $task = $this->buildTask($this->batch(), "rollback batch 5\n", true, ['2026_02_01_000001_create_forms.php']);

        verify(function () use ($task) {
            $task->execute();
        })->callableThrows(\RuntimeException::class);

        verify($this->ran)->same([]);
        verify($this->getOutput())->stringContainsString('2026_02_01_000001_create_forms');
    }

    public function testNothingToRollBack()
    {
        $task = $this->buildTask([], '');

        $task->execute();

        verify($this->ran)->same([]);
        verify($this->getOutput())->stringContainsString('Nothing to roll back');
    }
}
