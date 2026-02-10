<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\ModifyForeignKeyActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\ModifyForeignKeyActionHandler
 */
final class ModifyForeignKeyActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new ModifyForeignKeyActionHandler();
        verify($handler->getType())->equals('modify_foreign_key');
    }

    public function testBuildStepWithValidActionReturnsTwoSteps()
    {
        $handler = new ModifyForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'constraint_name' => 'orders_fk_user_id',
            'column' => 'user_id',
            'foreign_table' => 'users',
            'foreign_field' => 'id',
            'on_delete' => 'SET NULL',
            'on_update' => 'CASCADE',
        ];

        $steps = $handler->buildStep($action);
        verify($steps)->isArray();
        verify($steps)->arrayCount(2);
        verify($steps[0])->instanceOf(QueryStep::class);
        verify($steps[1])->instanceOf(QueryStep::class);
        verify($steps[0]->getQuery())->stringContainsString('ALTER TABLE `orders`');
        verify($steps[0]->getQuery())->stringContainsString('DROP FOREIGN KEY `orders_fk_user_id`');
        verify($steps[1]->getQuery())->stringContainsString('ALTER TABLE `orders`');
        verify($steps[1]->getQuery())->stringContainsString('ADD CONSTRAINT `orders_fk_user_id`');
        verify($steps[1]->getQuery())->stringContainsString('FOREIGN KEY (`user_id`)');
        verify($steps[1]->getQuery())->stringContainsString('REFERENCES `users` (`id`)');
        verify($steps[1]->getQuery())->stringContainsString('ON DELETE SET NULL');
        verify($steps[1]->getQuery())->stringContainsString('ON UPDATE CASCADE');
        verify($steps[0]->getParams())->equals([]);
        verify($steps[1]->getParams())->equals([]);
    }

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new ModifyForeignKeyActionHandler();
        $action = [
            'constraint_name' => 'orders_fk_user_id',
            'column' => 'user_id',
            'foreign_table' => 'users',
            'foreign_field' => 'id',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenConstraintNameMissing()
    {
        $handler = new ModifyForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'column' => 'user_id',
            'foreign_table' => 'users',
            'foreign_field' => 'id',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class);
    }

    public function testBuildStepThrowsExceptionWhenColumnMissing()
    {
        $handler = new ModifyForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'constraint_name' => 'orders_fk_user_id',
            'foreign_table' => 'users',
            'foreign_field' => 'id',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class);
    }

    public function testBuildStepThrowsExceptionWhenForeignTableMissing()
    {
        $handler = new ModifyForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'constraint_name' => 'orders_fk_user_id',
            'column' => 'user_id',
            'foreign_field' => 'id',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class);
    }

    public function testBuildStepThrowsExceptionWhenForeignFieldMissing()
    {
        $handler = new ModifyForeignKeyActionHandler();
        $action = [
            'table_name' => 'orders',
            'constraint_name' => 'orders_fk_user_id',
            'column' => 'user_id',
            'foreign_table' => 'users',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class);
    }
}
