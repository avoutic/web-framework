<?php

namespace Tests\Unit\Migration;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use WebFramework\Database\Database;
use WebFramework\Migration\DatabaseManager;
use WebFramework\Migration\MigrationFileLoader;
use WebFramework\Migration\MigrationManager;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\MigrationFileLoader
 * @covers \WebFramework\Migration\MigrationManager
 */
final class MigrationFileScopeTest extends Unit
{
    private string $appDir;

    protected function _before(): void
    {
        $this->appDir = codecept_output_dir().'migration_scope_app';

        if (!is_dir($this->appDir.'/migrations'))
        {
            mkdir($this->appDir.'/migrations', 0o777, true);
        }
    }

    protected function _after(): void
    {
        foreach ((array) glob($this->appDir.'/migrations/*.php') as $file)
        {
            unlink((string) $file);
        }

        rmdir($this->appDir.'/migrations');
        rmdir($this->appDir);
    }

    private function writeMigration(string $name, string $php): void
    {
        file_put_contents($this->appDir.'/migrations/'.$name, '<?php '.$php);
    }

    /**
     * A migration with helper variables that have the same names as the variables of
     * the code that loads it.
     */
    private function writeHostileMigration(string $name): void
    {
        $this->writeMigration($name, <<<'PHP'
        $type = 'operator_note';
        $batch = 999;
        $direction = 'sideways';
        $dryRun = true;
        $migrationFile = 'other.php';
        $migrationPath = '/nowhere';

        return [
            'up' => ['actions' => [['type' => 'raw_query', 'query' => 'SELECT 1', 'params' => []]]],
            'down' => ['actions' => []],
        ];
        PHP);
    }

    /**
     * @param array<string, mixed> $database
     */
    private function buildManager(array $database, mixed $execute): MigrationManager
    {
        return new MigrationManager(
            $this->makeEmpty(Container::class, ['get' => $this->appDir]),
            $this->makeEmpty(Database::class, $database),
            $this->makeEmpty(DatabaseManager::class, ['execute' => $execute]),
            fopen('php://memory', 'w+'),
        );
    }

    public function testLoaderReturnsTheArrayOfTheFile()
    {
        $this->writeMigration('plain.php', "return ['up' => ['actions' => []]];");

        verify((new MigrationFileLoader())->load($this->appDir.'/migrations/plain.php'))
            ->same(['up' => ['actions' => []]])
        ;
    }

    public function testLoaderRefusesAFileThatDoesNotReturnAnArray()
    {
        $this->writeMigration('broken.php', "return 'nope';");

        verify(function () {
            (new MigrationFileLoader())->load($this->appDir.'/migrations/broken.php');
        })->callableThrows(\RuntimeException::class);
    }

    public function testMigrationCannotChangeHowItIsRecorded()
    {
        $this->writeHostileMigration('2026_01_01_000001_hostile.php');

        $manager = $this->buildManager(
            [
                'insertQuery' => Expected::once(function (string $query, array $params) {
                    // Recorded with the name, type and batch that were asked for
                    verify($params)->same(['2026_01_01_000001_hostile', 'app', 5]);

                    return 1;
                }),
            ],
            Expected::once(function (array $actions, bool $dryRun = false) {
                verify($dryRun)->false();
            }),
        );

        $manager->runMigration('2026_01_01_000001_hostile.php', 5, 'app', 'up', false);
    }

    public function testMigrationCannotChangeTheDirectionThatIsChecked()
    {
        $this->writeHostileMigration('2026_01_01_000002_hostile.php');

        $manager = $this->buildManager([], Expected::never());

        verify($manager->supportsDirection('2026_01_01_000002_hostile.php', 'app', 'down'))->true();
        verify($manager->supportsDirection('2026_01_01_000002_hostile.php', 'app', 'sideways'))->false();
    }
}
