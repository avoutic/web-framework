<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\RenameColumnActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\RenameColumnActionHandler
 */
final class RenameColumnActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new RenameColumnActionHandler();
        verify($handler->getType())->equals('rename_column');
    }

    public function testBuildStepWithValidAction()
    {
        $handler = new RenameColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
            'name' => 'old_name',
            'new_name' => 'new_name',
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('ALTER TABLE `test_table`');
        verify($step->getQuery())->stringContainsString('RENAME COLUMN `old_name` TO `new_name`');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new RenameColumnActionHandler();
        $action = [
            'name' => 'old_name',
            'new_name' => 'new_name',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenNameMissing()
    {
        $handler = new RenameColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
            'new_name' => 'new_name',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No name specified');
    }

    public function testBuildStepThrowsExceptionWhenNewNameMissing()
    {
        $handler = new RenameColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
            'name' => 'old_name',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No new_name specified');
    }
}
