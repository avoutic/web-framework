<?php

namespace Tests\Unit\RepositoryQuery;

use Carbon\Carbon;
use Codeception\Test\Unit;
use Tests\Support\TestRepository;
use WebFramework\Entity\EntityCollection;
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
        $capturedQuery = null;
        $capturedParams = null;

        $repository = $this->make(TestRepository::class, [
            'getFromQuery' => function (string $query, array $params) use (&$capturedQuery, &$capturedParams) {
                $capturedQuery = $query;
                $capturedParams = $params;

                return $this->makeEmpty(EntityCollection::class, []);
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

        $instance->where([
            'OR' => [
                ['active' => true],
                ['age' => 20],
            ],
        ]);

        $instance->execute();

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
        $capturedQuery = null;
        $capturedParams = null;

        $repository = $this->make(TestRepository::class, [
            'getFromQuery' => function (string $query, array $params) use (&$capturedQuery, &$capturedParams) {
                $capturedQuery = $query;
                $capturedParams = $params;

                return $this->makeEmpty(EntityCollection::class, []);
            },
        ]);

        $instance = $this->make(
            RepositoryQuery::class,
            [
                'repository' => $repository,
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
            ->first()
        ;

        $expectedQuery = <<<'SQL'
        SELECT id, `queue_name`, `available_at`, `attempts`, `reserved_at`, `completed_at`
        FROM jobs
        WHERE `queue_name` = ? AND
              `available_at` <= ? AND
              `attempts` < `max_attempts` AND
              (`reserved_at` IS NULL OR `reserved_at` < ?) AND
              `completed_at` IS NULL
        ORDER BY `available_at` ASC, `id` ASC
        LIMIT ?
        FOR UPDATE SKIP LOCKED
SQL;

        self::assertSame(
            preg_replace('/\s+/', ' ', trim($expectedQuery)),
            preg_replace('/\s+/', ' ', trim($capturedQuery))
        );
        self::assertSame(['test_queue', $now, $staleThreshold, 1], $capturedParams);
    }
}
