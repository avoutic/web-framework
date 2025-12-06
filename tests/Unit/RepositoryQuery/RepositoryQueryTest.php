<?php

namespace Tests\Unit\RepositoryQuery;

use Carbon\Carbon;
use Codeception\Test\Unit;
use Tests\Support\TestRepository;
use WebFramework\Repository\Column;
use WebFramework\Repository\RepositoryQuery;

/**
 * @internal
 *
 * @covers \WebFramework\Repository\RepositoryQuery
 */
final class RepositoryQueryTest extends Unit
{
    public function testSimpleCount()
    {
        $capturedQuery = null;
        $capturedParams = null;

        $repository = $this->makeEmpty(TestRepository::class, [
            'getAggregateFromQuery' => function (string $query, array $params) use (&$capturedQuery, &$capturedParams) {
                $capturedQuery = $query;
                $capturedParams = $params;

                return 0;
            },
        ]);

        $instance = $this->make(
            RepositoryQuery::class,
            [
                'repository' => $repository,
                'tableName' => 'test_entities',
                'baseFields' => ['name', 'email', 'age', 'active', 'secret_field', 'created_at'],
            ],
        );

        $instance->count();

        $expectedQuery = <<<'SQL'
        SELECT COUNT(*) AS `aggregate`
        FROM test_entities
SQL;

        self::assertSame(
            preg_replace('/\s+/', ' ', trim($expectedQuery)),
            preg_replace('/\s+/', ' ', trim($capturedQuery))
        );
        self::assertSame([], $capturedParams);
    }

    public function testFilteredCount()
    {
        $capturedQuery = null;
        $capturedParams = null;

        $repository = $this->makeEmpty(TestRepository::class, [
            'getAggregateFromQuery' => function (string $query, array $params) use (&$capturedQuery, &$capturedParams) {
                $capturedQuery = $query;
                $capturedParams = $params;

                return 5;
            },
            'getFilterArray' => [
                'query' => '`active` = ?',
                'params' => [true],
            ],
        ]);

        $instance = $this->make(
            RepositoryQuery::class,
            [
                'repository' => $repository,
                'tableName' => 'test_entities',
                'baseFields' => ['name', 'email', 'age', 'active', 'secret_field', 'created_at'],
            ],
        );

        $instance->where(['active' => true]);
        $instance->count();

        $expectedQuery = <<<'SQL'
        SELECT COUNT(*) AS `aggregate`
        FROM test_entities
        WHERE `active` = ?
SQL;

        self::assertSame(
            preg_replace('/\s+/', ' ', trim($expectedQuery)),
            preg_replace('/\s+/', ' ', trim($capturedQuery))
        );
        self::assertSame([true], $capturedParams);
    }

    public function testOrFilter()
    {
        $instance = $this->make(
            RepositoryQuery::class,
            [
                'repository' => $this->make(TestRepository::class, []),
                'tableName' => 'test_entities',
                'baseFields' => ['name', 'email', 'age', 'active', 'secret_field', 'created_at'],
            ],
        );

        $instance->where([
            'OR' => [
                ['active' => true],
                ['age' => 20],
            ],
        ]);

        [$capturedQuery, $capturedParams] = $instance->toSql();

        $expectedQuery = <<<'SQL'
        SELECT id, `name`, `email`, `age`, `active`, `secret_field`, `created_at`
        FROM test_entities
        WHERE (`active` = ? OR `age` = ?)
SQL;

        self::assertSame(
            preg_replace('/\s+/', ' ', trim($expectedQuery)),
            preg_replace('/\s+/', ' ', trim($capturedQuery))
        );
        self::assertSame([true, 20], $capturedParams);
    }

    public function testComplexQuery()
    {
        $instance = $this->make(
            RepositoryQuery::class,
            [
                'repository' => $this->make(TestRepository::class, []),
                'tableName' => 'jobs',
                'baseFields' => ['queue_name', 'available_at', 'attempts', 'reserved_at', 'completed_at'],
            ],
        );

        $now = Carbon::now()->getTimestamp();
        $staleThreshold = $now - 300;

        $instance
            ->where([
                'queue_name' => 'test_queue',
                'available_at' => ['<=', $now],
                'attempts' => ['<', new Column('max_attempts')],
                'reserved_at' => [
                    'OR' => [
                        null,
                        ['<', $staleThreshold],
                    ],
                ],
                'completed_at' => null,
            ])
            ->orderByAsc('available_at')
            ->orderByAsc('id')
            ->lockForUpdate(true)
        ;

        [$capturedQuery, $capturedParams] = $instance->toSql();

        $expectedQuery = <<<'SQL'
        SELECT id, `queue_name`, `available_at`, `attempts`, `reserved_at`, `completed_at`
        FROM jobs
        WHERE `queue_name` = ? AND
              `available_at` <= ? AND
              `attempts` < `max_attempts` AND
              (`reserved_at` IS NULL OR `reserved_at` < ?) AND
              `completed_at` IS NULL
        ORDER BY `available_at` ASC, `id` ASC
        FOR UPDATE SKIP LOCKED
SQL;

        self::assertSame(
            preg_replace('/\s+/', ' ', trim($expectedQuery)),
            preg_replace('/\s+/', ' ', trim($capturedQuery))
        );
        self::assertSame(['test_queue', $now, $staleThreshold], $capturedParams);
    }

    public function testWhenQueryTruthy()
    {
        $instance = $this->make(
            RepositoryQuery::class,
            [
                'repository' => $this->make(TestRepository::class, []),
                'tableName' => 'test_entities',
                'baseFields' => ['name', 'email', 'age', 'active', 'secret_field', 'created_at'],
            ],
        );

        $cutoff = Carbon::now()->subSeconds(100)->getTimestamp();
        $userId = 1;
        $ip = '127.0.0.1';

        $query = $instance
            ->where([
                'timestamp' => ['>', $cutoff],
            ])
            ->when(
                $userId !== null,
                fn ($query) => $query->where([
                    'OR' => [
                        'ip' => $ip,
                        'user_id' => $userId,
                    ],
                ]),
                fn ($query) => $query->where([
                    'ip' => $ip,
                ]),
            )
        ;

        [$capturedQuery, $capturedParams] = $query->toSql();

        $expectedQuery = <<<'SQL'
        SELECT id, `name`, `email`, `age`, `active`, `secret_field`, `created_at`
        FROM test_entities
        WHERE `timestamp` > ? AND
              (`ip` = ? OR `user_id` = ?)
SQL;

        self::assertSame(
            preg_replace('/\s+/', ' ', trim($expectedQuery)),
            preg_replace('/\s+/', ' ', trim($capturedQuery))
        );
        self::assertSame([$cutoff, $ip, $userId], $capturedParams);
    }

    public function testWhenQueryFalsy()
    {
        $instance = $this->make(
            RepositoryQuery::class,
            [
                'repository' => $this->make(TestRepository::class, []),
                'tableName' => 'test_entities',
                'baseFields' => ['name', 'email', 'age', 'active', 'secret_field', 'created_at'],
            ],
        );

        $cutoff = Carbon::now()->subSeconds(100)->getTimestamp();
        $userId = null;
        $ip = '127.0.0.1';

        $query = $instance
            ->where([
                'timestamp' => ['>', $cutoff],
            ])
            ->when(
                $userId !== null,
                fn ($query) => $query->where([
                    'OR' => [
                        'ip' => $ip,
                        'user_id' => $userId,
                    ],
                ]),
                fn ($query) => $query->where([
                    'ip' => $ip,
                ]),
            )
        ;

        [$capturedQuery, $capturedParams] = $query->toSql();

        $expectedQuery = <<<'SQL'
        SELECT id, `name`, `email`, `age`, `active`, `secret_field`, `created_at`
        FROM test_entities
        WHERE `timestamp` > ? AND
              `ip` = ?
SQL;

        self::assertSame(
            preg_replace('/\s+/', ' ', trim($expectedQuery)),
            preg_replace('/\s+/', ' ', trim($capturedQuery))
        );
        self::assertSame([$cutoff, $ip], $capturedParams);
    }
}
