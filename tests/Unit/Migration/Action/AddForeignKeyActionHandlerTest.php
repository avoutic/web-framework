<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\AddForeignKeyActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\AddForeignKeyActionHandler
 */
final class AddForeignKeyActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new AddForeignKeyActionHandler();
        verify($handler->getType())->equals('add_foreign_key');
    }

    public function testBuildStepWithValidAction()
    {
        $handler = new AddForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'column' => 'user_id',
            'foreign_table' => 'users',
            'foreign_field' => 'id',
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE',
            'constraint_name' => 'orders_fk_user_id',
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('ALTER TABLE `orders`');
        verify($step->getQuery())->stringContainsString('ADD CONSTRAINT `orders_fk_user_id`');
        verify($step->getQuery())->stringContainsString('FOREIGN KEY (`user_id`)');
        verify($step->getQuery())->stringContainsString('REFERENCES `users` (`id`)');
        verify($step->getQuery())->stringContainsString('ON DELETE CASCADE');
        verify($step->getQuery())->stringContainsString('ON UPDATE CASCADE');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepWithoutConstraintNameGeneratesName()
    {
        $handler = new AddForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'column' => 'user_id',
            'foreign_table' => 'users',
            'foreign_field' => 'id',
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('CONSTRAINT `foreign_orders_user_id`');
        verify($step->getQuery())->stringContainsString('REFERENCES `users` (`id`)');
    }

    public function testBuildStepWithOptionalOnDeleteAndOnUpdate()
    {
        $handler = new AddForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'column' => 'user_id',
            'foreign_table' => 'users',
            'foreign_field' => 'id',
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString('FOREIGN KEY (`user_id`)');
        verify($step->getQuery())->stringContainsString('REFERENCES `users` (`id`)');
    }

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new AddForeignKeyActionHandler();
        $action = [
            'column' => 'user_id',
            'foreign_table' => 'users',
            'foreign_field' => 'id',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenColumnMissing()
    {
        $handler = new AddForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'foreign_table' => 'users',
            'foreign_field' => 'id',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class);
    }

    public function testBuildStepThrowsExceptionWhenForeignTableMissing()
    {
        $handler = new AddForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'column' => 'user_id',
            'foreign_field' => 'id',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class);
    }

    public function testBuildStepThrowsExceptionWhenForeignFieldMissing()
    {
        $handler = new AddForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'column' => 'user_id',
            'foreign_table' => 'users',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class);
    }
}
