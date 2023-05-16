<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use WebFramework\Core\Database;
use WebFramework\Core\DatabaseResultWrapper;

/**
 * @internal
 *
 * @coversNothing
 */
final class DatabaseTest extends \Codeception\Test\Unit
{
    public Database $instance;

    public function testUnconnectedQuery()
    {
        $mysql = $this->makeEmpty(\mysqli::class, ['ping' => false]);
        $this->instance = new Database($mysql);

        verify(function () { $this->instance->query('', []); })
            ->callableThrows(\RuntimeException::class, 'Database connection not available');
    }

    public function testUnconnectedInsertQuery()
    {
        $mysql = $this->makeEmpty(\mysqli::class, ['ping' => false]);
        $this->instance = new Database($mysql);

        verify(function () { $this->instance->insert_query('', []); })
            ->callableThrows(\RuntimeException::class, 'Database connection not available');
    }

    public function testTableExistsFails()
    {
        $this->instance = $this->construct(
            Database::class,
            [
                'database' => $this->makeEmpty(\mysqli::class),
            ],
            [
                'query' => false,
            ]
        );

        verify(function () { $this->instance->table_exists('not_existing'); })
            ->callableThrows(\RuntimeException::class, 'Check for table existence failed');
    }

    public function testStartTransactionOnce()
    {
        $this->instance = $this->construct(
            Database::class,
            [
                'database' => $this->makeEmpty(\mysqli::class),
            ],
            [
                'query' => Expected::exactly(2, $this->makeEmpty(DatabaseResultWrapper::class)),
            ]
        );

        $this->instance->start_transaction();
        $this->instance->start_transaction();
        $this->instance->start_transaction();

        verify($this->instance->get_transaction_depth())
            ->equals(3);

        $this->instance->commit_transaction();
        $this->instance->commit_transaction();
        $this->instance->commit_transaction();

        verify($this->instance->get_transaction_depth())
            ->equals(0);
    }
}
