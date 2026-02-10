<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\DropForeignKeyActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\DropForeignKeyActionHandler
 */
final class DropForeignKeyActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new DropForeignKeyActionHandler();
        verify($handler->getType())->equals('drop_foreign_key');
    }

    public function testBuildStepWithValidAction()
    {
        $handler = new DropForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'constraint_name' => 'orders_fk_user_id',
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('ALTER TABLE `orders`');
        verify($step->getQuery())->stringContainsString('DROP FOREIGN KEY `orders_fk_user_id`');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new DropForeignKeyActionHandler();
        $action = [
            'constraint_name' => 'orders_fk_user_id',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenConstraintNameMissing()
    {
        $handler = new DropForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class);
    }
}
