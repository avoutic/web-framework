<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use WebFramework\Core\DatabaseResultWrapper;
use WebFramework\Core\MysqliDatabase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DatabaseTest extends \Codeception\Test\Unit
{
    public function testUnconnectedQuery()
    {
        $mysql = $this->makeEmpty(\mysqli::class, ['ping' => false]);
        $instance = new MysqliDatabase($mysql);

        verify(function () use ($instance) { $instance->query('', []); })
            ->callableThrows(\RuntimeException::class, 'Database connection not available');
    }

    public function testUnconnectedInsertQuery()
    {
        $mysql = $this->makeEmpty(\mysqli::class, ['ping' => false]);
        $instance = new MysqliDatabase($mysql);

        verify(function () use ($instance) { $instance->insertQuery('', []); })
            ->callableThrows(\RuntimeException::class, 'Database connection not available');
    }

    public function testTableExistsFails()
    {
        $instance = $this->construct(
            MysqliDatabase::class,
            [
                'database' => $this->makeEmpty(\mysqli::class),
            ],
            [
                'query' => false,
            ]
        );

        verify(function () use ($instance) { $instance->tableExists('not_existing'); })
            ->callableThrows(\RuntimeException::class, 'Check for table existence failed');
    }

    public function testStartTransactionOnce()
    {
        $instance = $this->construct(
            MysqliDatabase::class,
            [
                'database' => $this->makeEmpty(\mysqli::class),
            ],
            [
                'query' => Expected::exactly(2, $this->makeEmpty(DatabaseResultWrapper::class)),
            ]
        );

        $instance->startTransaction();
        $instance->startTransaction();
        $instance->startTransaction();

        verify($instance->getTransactionDepth())
            ->equals(3);

        $instance->commitTransaction();
        $instance->commitTransaction();
        $instance->commitTransaction();

        verify($instance->getTransactionDepth())
            ->equals(0);
    }
}
