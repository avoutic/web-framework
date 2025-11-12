<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\RenameTableActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\RenameTableActionHandler
 */
final class RenameTableActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new RenameTableActionHandler();
        verify($handler->getType())->equals('rename_table');
    }

    public function testBuildStepWithValidAction()
    {
        $handler = new RenameTableActionHandler();
        $action = [
            'table_name' => 'old_table',
            'new_name' => 'new_table',
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('ALTER TABLE `old_table`');
        verify($step->getQuery())->stringContainsString('RENAME TO `new_table`');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new RenameTableActionHandler();
        $action = [
            'new_name' => 'new_table',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenNewNameMissing()
    {
        $handler = new RenameTableActionHandler();
        $action = [
            'table_name' => 'old_table',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No new_name specified');
    }
}
