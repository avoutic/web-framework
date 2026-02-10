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

    public function testBuildStepWithValidAction()
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

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('ALTER TABLE `orders`');
        verify($step->getQuery())->stringContainsString('DROP FOREIGN KEY `orders_fk_user_id`');
        verify($step->getQuery())->stringContainsString('ADD CONSTRAINT `orders_fk_user_id`');
        verify($step->getQuery())->stringContainsString('FOREIGN KEY (`user_id`)');
        verify($step->getQuery())->stringContainsString('REFERENCES `users` (`id`)');
        verify($step->getQuery())->stringContainsString('ON DELETE SET NULL');
        verify($step->getQuery())->stringContainsString('ON UPDATE CASCADE');
        verify($step->getParams())->equals([]);
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
