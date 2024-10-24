<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use WebFramework\Core\DatabaseResultWrapper;
use WebFramework\Core\MysqliDatabase;
use WebFramework\Core\NullInstrumentation;

/**
 * @internal
 *
 * @coversNothing
 */
final class DatabaseTest extends Unit
{
    public function testUnconnectedQuery()
    {
        $mysql = $this->makeEmpty(\mysqli::class, ['ping' => false]);
        $instance = new MysqliDatabase($mysql, new NullInstrumentation());

        verify(function () use ($instance) { $instance->query('', []); })
            ->callableThrows(\RuntimeException::class, 'Database connection not available')
        ;
    }

    public function testUnconnectedInsertQuery()
    {
        $mysql = $this->makeEmpty(\mysqli::class, ['ping' => false]);
        $instance = new MysqliDatabase($mysql, new NullInstrumentation());

        verify(function () use ($instance) { $instance->insertQuery('', []); })
            ->callableThrows(\RuntimeException::class, 'Database connection not available')
        ;
    }

    public function testStartTransactionOnce()
    {
        $instance = $this->construct(
            MysqliDatabase::class,
            [
                'database' => $this->makeEmpty(\mysqli::class),
                'instrumentation' => $this->makeEmpty(NullInstrumentation::class),
            ],
            [
                'query' => Expected::exactly(2, $this->makeEmpty(DatabaseResultWrapper::class)),
            ]
        );

        $instance->startTransaction();
        $instance->startTransaction();
        $instance->startTransaction();

        verify($instance->getTransactionDepth())
            ->equals(3)
        ;

        $instance->commitTransaction();
        $instance->commitTransaction();
        $instance->commitTransaction();

        verify($instance->getTransactionDepth())
            ->equals(0)
        ;
    }
}
