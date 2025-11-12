<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\ModifyColumnTypeActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\ModifyColumnTypeActionHandler
 */
final class ModifyColumnTypeActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new ModifyColumnTypeActionHandler();
        verify($handler->getType())->equals('modify_column_type');
    }

    public function testBuildStepWithValidAction()
    {
        $handler = new ModifyColumnTypeActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'name' => 'name',
                'type' => 'varchar',
                'size' => 255,
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('ALTER TABLE `test_table`');
        verify($step->getQuery())->stringContainsString('MODIFY `name` VARCHAR(255)');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepWithNullField()
    {
        $handler = new ModifyColumnTypeActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'name' => 'description',
                'type' => 'text',
                'null' => true,
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString('NULL');
    }

    public function testBuildStepWithDefaultValue()
    {
        $handler = new ModifyColumnTypeActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'name' => 'status',
                'type' => 'varchar',
                'size' => 50,
                'default' => 'active',
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString("DEFAULT 'active'");
    }

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new ModifyColumnTypeActionHandler();
        $action = [
            'field' => [
                'name' => 'name',
                'type' => 'varchar',
                'size' => 255,
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenFieldMissing()
    {
        $handler = new ModifyColumnTypeActionHandler();
        $action = [
            'table_name' => 'test_table',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No field array specified');
    }

    public function testBuildStepThrowsExceptionWhenFieldNameMissing()
    {
        $handler = new ModifyColumnTypeActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'type' => 'varchar',
                'size' => 255,
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No field name specified');
    }

    public function testBuildStepThrowsExceptionWhenFieldTypeMissing()
    {
        $handler = new ModifyColumnTypeActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'name' => 'name',
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No field type specified');
    }
}
