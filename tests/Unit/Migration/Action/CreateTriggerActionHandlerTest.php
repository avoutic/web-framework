<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\CreateTriggerActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\CreateTriggerActionHandler
 */
final class CreateTriggerActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new CreateTriggerActionHandler();
        verify($handler->getType())->equals('create_trigger');
    }

    public function testBuildStepWithValidAction()
    {
        $handler = new CreateTriggerActionHandler();
        $action = [
            'table_name' => 'test_table',
            'trigger' => [
                'name' => 'test_trigger',
                'time' => 'BEFORE',
                'event' => 'INSERT',
                'action' => 'BEGIN SET NEW.created_at = NOW(); END',
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('CREATE TRIGGER `test_trigger`');
        verify($step->getQuery())->stringContainsString('BEFORE INSERT');
        verify($step->getQuery())->stringContainsString('ON `test_table`');
        verify($step->getQuery())->stringContainsString('FOR EACH ROW');
        verify($step->getQuery())->stringContainsString('BEGIN SET NEW.created_at = NOW(); END');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepWithAfterUpdate()
    {
        $handler = new CreateTriggerActionHandler();
        $action = [
            'table_name' => 'test_table',
            'trigger' => [
                'name' => 'update_trigger',
                'time' => 'AFTER',
                'event' => 'UPDATE',
                'action' => 'BEGIN SET NEW.updated_at = NOW(); END',
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString('AFTER UPDATE');
    }

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new CreateTriggerActionHandler();
        $action = [
            'trigger' => [
                'name' => 'test_trigger',
                'time' => 'BEFORE',
                'event' => 'INSERT',
                'action' => 'BEGIN END',
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenTriggerMissing()
    {
        $handler = new CreateTriggerActionHandler();
        $action = [
            'table_name' => 'test_table',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No trigger array specified');
    }

    public function testBuildStepThrowsExceptionWhenTriggerNameMissing()
    {
        $handler = new CreateTriggerActionHandler();
        $action = [
            'table_name' => 'test_table',
            'trigger' => [
                'time' => 'BEFORE',
                'event' => 'INSERT',
                'action' => 'BEGIN END',
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No trigger name specified');
    }

    public function testBuildStepThrowsExceptionWhenTriggerTimeMissing()
    {
        $handler = new CreateTriggerActionHandler();
        $action = [
            'table_name' => 'test_table',
            'trigger' => [
                'name' => 'test_trigger',
                'event' => 'INSERT',
                'action' => 'BEGIN END',
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No trigger time specified');
    }

    public function testBuildStepThrowsExceptionWhenTriggerEventMissing()
    {
        $handler = new CreateTriggerActionHandler();
        $action = [
            'table_name' => 'test_table',
            'trigger' => [
                'name' => 'test_trigger',
                'time' => 'BEFORE',
                'action' => 'BEGIN END',
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No trigger event specified');
    }

    public function testBuildStepThrowsExceptionWhenTriggerActionMissing()
    {
        $handler = new CreateTriggerActionHandler();
        $action = [
            'table_name' => 'test_table',
            'trigger' => [
                'name' => 'test_trigger',
                'time' => 'BEFORE',
                'event' => 'INSERT',
            ],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No trigger action specified');
    }
}
