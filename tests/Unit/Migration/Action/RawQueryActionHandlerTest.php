<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Test\Unit;
use WebFramework\Migration\Action\RawQueryActionHandler;
use WebFramework\Migration\QueryStep;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\RawQueryActionHandler
 */
final class RawQueryActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $handler = new RawQueryActionHandler();
        verify($handler->getType())->equals('raw_query');
    }

    public function testBuildStepWithValidAction()
    {
        $handler = new RawQueryActionHandler();
        $action = [
            'query' => 'SELECT * FROM users WHERE id = ?',
            'params' => [123],
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(QueryStep::class);
        verify($step->getQuery())->equals('SELECT * FROM users WHERE id = ?');
        verify($step->getParams())->equals([123]);
    }

    public function testBuildStepWithEmptyParams()
    {
        $handler = new RawQueryActionHandler();
        $action = [
            'query' => 'SELECT * FROM users',
            'params' => [],
        ];

        $step = $handler->buildStep($action);
        verify($step->getQuery())->equals('SELECT * FROM users');
        verify($step->getParams())->equals([]);
    }

    public function testBuildStepWithMultipleParams()
    {
        $handler = new RawQueryActionHandler();
        $action = [
            'query' => 'UPDATE users SET name = ?, email = ? WHERE id = ?',
            'params' => ['John', 'john@example.com', 123],
        ];

        $step = $handler->buildStep($action);
        verify($step->getParams())->equals(['John', 'john@example.com', 123]);
    }

    public function testBuildStepThrowsExceptionWhenQueryMissing()
    {
        $handler = new RawQueryActionHandler();
        $action = [
            'params' => [],
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No query specified');
    }

    public function testBuildStepThrowsExceptionWhenParamsMissing()
    {
        $handler = new RawQueryActionHandler();
        $action = [
            'query' => 'SELECT * FROM users',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No params array specified');
    }
}
