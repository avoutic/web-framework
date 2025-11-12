<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\CreateTableActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\CreateTableActionHandler
 */
final class CreateTableActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new CreateTableActionHandler();
        verify($handler->getType())->equals('create_table');
    }

    public function testBuildStepWithValidAction()
    {
        $handler = new CreateTableActionHandler();
        $action = [
            'table_name' => 'test_table',
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'varchar',
                    'size' => 255,
                ],
            ],
            'constraints' => [],
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->stringContainsString('CREATE TABLE `test_table`');
        verify($step->getQuery())->stringContainsString('`id` int(11) NOT NULL AUTO_INCREMENT');
        verify($step->getQuery())->stringContainsString('PRIMARY KEY (`id`)');
        verify($step->getQuery())->stringContainsString('`name` VARCHAR(255)');
        verify($step->getQuery())->stringContainsString('ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepWithMultipleFields()
    {
        $handler = new CreateTableActionHandler();
        $action = [
            'table_name' => 'test_table',
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'varchar',
                    'size' => 255,
                ],
                [
                    'name' => 'email',
                    'type' => 'varchar',
                    'size' => 100,
                ],
            ],
            'constraints' => [],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString('`name` VARCHAR(255)');
        verify($step->getQuery())->stringContainsString('`email` VARCHAR(100)');
    }

    public function testBuildStepWithConstraints()
    {
        $handler = new CreateTableActionHandler();
        $action = [
            'table_name' => 'test_table',
            'fields' => [
                [
                    'name' => 'email',
                    'type' => 'varchar',
                    'size' => 255,
                ],
            ],
            'constraints' => [
                [
                    'type' => 'unique',
                    'values' => ['email'],
                ],
            ],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->stringContainsString('UNIQUE KEY');
    }

    public function testBuildStepThrowsExceptionWhenTableNameMissing()
    {
        $handler = new CreateTableActionHandler();
        $action = [
            'fields' => [],
            'constraints' => [],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No table_name specified');
    }

    public function testBuildStepThrowsExceptionWhenFieldsMissing()
    {
        $handler = new CreateTableActionHandler();
        $action = [
            'table_name' => 'test_table',
            'constraints' => [],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No fields array specified');
    }

    public function testBuildStepThrowsExceptionWhenConstraintsMissing()
    {
        $handler = new CreateTableActionHandler();
        $action = [
            'table_name' => 'test_table',
            'fields' => [],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No constraints array specified');
    }
}
