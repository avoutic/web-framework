<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\AddColumnActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\AddColumnActionHandler
 */
final class AddColumnActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new AddColumnActionHandler();
        verify($handler->getType())->equals('add_column');
    }

    public function testBuildStepWithValidAction()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'name' => 'new_column',
                'type' => 'varchar',
                'size' => 100,
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('ALTER TABLE `test_table`');
        verify($step->getQuery())->stringContainsString('ADD `new_column` VARCHAR(100)');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepWithDefaultValue()
    {
        $handler = new AddColumnActionHandler();
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

    public function testBuildStepWithNullField()
    {
        $handler = new AddColumnActionHandler();
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

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'field' => [
                'name' => 'new_column',
                'type' => 'varchar',
                'size' => 100,
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenFieldMissing()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No field array specified');
    }

    public function testBuildStepThrowsExceptionWhenFieldNameMissing()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'type' => 'varchar',
                'size' => 100,
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No field name specified');
    }

    public function testBuildStepThrowsExceptionWhenFieldTypeMissing()
    {
        $handler = new AddColumnActionHandler();
        $action = [
            'table_name' => 'test_table',
            'field' => [
                'name' => 'new_column',
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No field type specified');
    }
}
