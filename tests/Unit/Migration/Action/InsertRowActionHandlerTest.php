<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\InsertRowActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\InsertRowActionHandler
 */
final class InsertRowActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new InsertRowActionHandler();
        verify($handler->getType())->equals('insert_row');
    }

    public function testBuildStepWithValidAction()
    {
        $handler = new InsertRowActionHandler();
        $action = [
            'table_name' => 'test_table',
            'values' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('INSERT INTO `test_table`');
        verify($step->getQuery())->stringContainsString('SET');
        verify($step->getQuery())->stringContainsString('`name` = ?');
        verify($step->getQuery())->stringContainsString('`email` = ?');
        verify($step->getParams())->equals(['John Doe', 'john@example.com']);
    }

    public function testBuildStepWithNullValue()
    {
        $handler = new InsertRowActionHandler();
        $action = [
            'table_name' => 'test_table',
            'values' => [
                'name' => 'John Doe',
                'description' => null,
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString('`description` = NULL');
        verify($step->getParams())->equals(['John Doe']);
    }

    public function testBuildStepWithSingleValue()
    {
        $handler = new InsertRowActionHandler();
        $action = [
            'table_name' => 'test_table',
            'values' => [
                'name' => 'John Doe',
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString('`name` = ?');
        verify($step->getParams())->equals(['John Doe']);
    }

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new InsertRowActionHandler();
        $action = [
            'values' => [
                'name' => 'John Doe',
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenValuesMissing()
    {
        $handler = new InsertRowActionHandler();
        $action = [
            'table_name' => 'test_table',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No values array specified');
    }
}
