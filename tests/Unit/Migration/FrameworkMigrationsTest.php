<?php

namespace Tests\Unit\Migration;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\ModifyColumnTypeActionHandler;
use WebFramework\Migration\Action\RenameTableActionHandler;
use WebFramework\Migration\MigrationFileLoader;

/**
 * @internal
 *
 * @coversNothing
 */
final class FrameworkMigrationsTest extends Unit
{
    /**
     * @return array<mixed>
     */
    private function load(string $file): array
    {
        return (new MigrationFileLoader())->load(dirname(__DIR__, 3).'/migrations/'.$file);
    }

    /**
     * @param array<array<string, mixed>> $actions
     *
     * @return array<string>
     */
    private function buildQueries(array $actions): array
    {
        $handlers = [
            'modify_column_type' => new ModifyColumnTypeActionHandler(),
            'rename_table' => new RenameTableActionHandler(),
        ];

        return array_map(
            fn (array $action) => (string) preg_replace('/\s+/', ' ', $handlers[$action['type']]->buildStep($action)->getQuery()),
            $actions,
        );
    }

    public function testEveryFrameworkMigrationHasUpAndDownActions()
    {
        $files = glob(dirname(__DIR__, 3).'/migrations/*.php');

        verify(count((array) $files))->greaterThan(0);

        foreach ((array) $files as $file)
        {
            $data = $this->load(basename((string) $file));

            verify(isset($data['up']['actions']) && is_array($data['up']['actions']))->true();
            verify(isset($data['down']['actions']) && is_array($data['down']['actions']))->true();
        }
    }

    public function testStoredValuesCanHoldMoreThanAShortString()
    {
        $data = $this->load('2026_11_24_120000_widen_value_of_stored_values_tables.php');

        verify($this->buildQueries($data['up']['actions']))->same([
            'ALTER TABLE `stored_user_values` MODIFY `value` TEXT NOT NULL',
            'ALTER TABLE `stored_values` MODIFY `value` TEXT NOT NULL',
        ]);
        verify($this->buildQueries($data['down']['actions']))->same([
            'ALTER TABLE `stored_user_values` MODIFY `value` VARCHAR(45) NOT NULL',
            'ALTER TABLE `stored_values` MODIFY `value` VARCHAR(45) NOT NULL',
        ]);
    }

    public function testUserConfigValuesIsRenamedAndNotDropped()
    {
        $data = $this->load('2026_11_24_120100_mark_user_config_values_table_as_deprecated.php');

        $up = $this->buildQueries($data['up']['actions']);

        verify(count($up))->equals(1);
        verify($up[0])->stringContainsString('user_config_values');
        verify($up[0])->stringContainsString('user_config_values_deprecated');
        verify(strtoupper($up[0]))->stringNotContainsString('DROP');
    }

    public function testConfigValuesIsRenamedAndNotDropped()
    {
        $data = $this->load('2026_11_24_120200_mark_config_values_table_as_deprecated.php');

        $up = $this->buildQueries($data['up']['actions']);
        $down = $this->buildQueries($data['down']['actions']);

        verify(count($up))->equals(1);
        verify($up[0])->stringContainsString('`config_values`');
        verify($up[0])->stringContainsString('`config_values_deprecated`');
        verify(strtoupper($up[0]))->stringNotContainsString('DROP');
        verify(count($down))->equals(1);
        verify($down[0])->stringContainsString('`config_values_deprecated`');
    }

    public function testConfigValuesIsRenamedAfterItsContentsWereCopied()
    {
        // The conversion from the old system reads config_values until stored_values is
        // filled from it. The rename has to come after that copy
        $files = array_map('basename', (array) glob(dirname(__DIR__, 3).'/migrations/*.php'));
        sort($files);

        $copy = array_search('2025_05_23_000001_create_stored_values_table.php', $files, true);
        $rename = array_search('2026_11_24_120200_mark_config_values_table_as_deprecated.php', $files, true);

        verify($copy)->notSame(false);
        verify($rename)->greaterThan($copy);
    }
}
