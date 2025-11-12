<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\AddConstraintActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\AddConstraintActionHandler
 */
final class AddConstraintActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new AddConstraintActionHandler();
        verify($handler->getType())->equals('add_constraint');
    }

    public function testBuildStepWithUniqueConstraint()
    {
        $handler = new AddConstraintActionHandler();
        $action = [
            'table_name' => 'test_table',
            'constraint' => [
                'type' => 'unique',
                'values' => ['email'],
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('ALTER TABLE `test_table`');
        verify($step->getQuery())->stringContainsString('UNIQUE KEY');
        verify($step->getQuery())->stringContainsString('`email`');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepWithUniqueConstraintWithName()
    {
        $handler = new AddConstraintActionHandler();
        $action = [
            'table_name' => 'test_table',
            'constraint' => [
                'type' => 'unique',
                'name' => 'unique_email',
                'values' => ['email'],
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString('`unique_email`');
    }

    public function testBuildStepWithIndexConstraint()
    {
        $handler = new AddConstraintActionHandler();
        $action = [
            'table_name' => 'test_table',
            'constraint' => [
                'type' => 'index',
                'name' => 'idx_email',
                'values' => ['email'],
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('ALTER TABLE `test_table`');
        verify($step->getQuery())->stringContainsString('INDEX `idx_email`');
        verify($step->getQuery())->stringContainsString('`email`');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepWithMultipleValues()
    {
        $handler = new AddConstraintActionHandler();
        $action = [
            'table_name' => 'test_table',
            'constraint' => [
                'type' => 'unique',
                'values' => ['user_id', 'role_id'],
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString('`user_id`, `role_id`');
    }

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new AddConstraintActionHandler();
        $action = [
            'constraint' => [
                'type' => 'unique',
                'values' => ['email'],
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenConstraintMissing()
    {
        $handler = new AddConstraintActionHandler();
        $action = [
            'table_name' => 'test_table',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No constraint array specified');
    }

    public function testBuildStepThrowsExceptionWhenConstraintTypeMissing()
    {
        $handler = new AddConstraintActionHandler();
        $action = [
            'table_name' => 'test_table',
            'constraint' => [
                'values' => ['email'],
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No constraint type specified');
    }

    public function testBuildStepThrowsExceptionWhenValuesMissing()
    {
        $handler = new AddConstraintActionHandler();
        $action = [
            'table_name' => 'test_table',
            'constraint' => [
                'type' => 'unique',
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'Values for unique constraint must be an array');
    }

    public function testBuildStepThrowsExceptionWhenIndexNameMissing()
    {
        $handler = new AddConstraintActionHandler();
        $action = [
            'table_name' => 'test_table',
            'constraint' => [
                'type' => 'index',
                'values' => ['email'],
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No name for index specified');
    }

    public function testBuildStepThrowsExceptionForUnknownConstraintType()
    {
        $handler = new AddConstraintActionHandler();
        $action = [
            'table_name' => 'test_table',
            'constraint' => [
                'type' => 'unknown',
                'values' => ['email'],
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\RuntimeException::class, "Unknown constraint type 'unknown'");
    }
}
