<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use WebFramework\Core\Database;
use WebFramework\Core\DatabaseResultWrapper;
use WebFramework\Migration\DatabaseManager;
use WebFramework\Task\Task;

/**
 * @internal
 *
 * @coversNothing
 */
final class DatabaseManagerTest extends Unit
{
    private function normalizeQueryStrings(array $queries): array
    {
        for ($i = 0; $i < count($queries); $i++)
        {
            $query = $queries[$i];
            $queryString = trim($query['query']);
            $queryString = str_replace("\n", ' ', $queryString);
            $queryString = preg_replace('/\s+/', ' ', $queryString);
            $queryString = str_replace(' ,', ',', $queryString);

            $queries[$i]['query'] = $queryString;
        }

        return $queries;
    }

    public function getQuerySaver(array &$usedQueries)
    {
        $databaseWrapper = $this->makeEmpty(DatabaseResultWrapper::class);

        $database = $this->makeEmpty(
            Database::class,
            [
                'query' => function ($queryString, $params) use (&$usedQueries, $databaseWrapper) {
                    $usedQueries[] = [
                        'query' => $queryString,
                        'params' => $params,
                    ];

                    return $databaseWrapper;
                },
                'startTransaction' => Expected::once(),
                'commitTransaction' => Expected::once(),
                'getLastError' => Expected::never(),
            ]
        );

        return $database;
    }

    public function getQuerySaverManager(array &$usedQueries)
    {
        $database = $this->getQuerySaver($usedQueries);
        $container = $this->makeEmpty(Container::class);

        return new DatabaseManager(
            $database,
            $container,
            fopen('php://memory', 'w')
        );
    }

    public function testExecuteCreateTable()
    {
        $usedQueries = [];

        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'create_table',
                    'table_name' => 'test_table',
                    'fields' => [
                        [
                            'name' => 'name',
                            'type' => 'varchar',
                            'size' => 255,
                        ],
                    ],
                    'constraints' => [
                    ],
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                    CREATE TABLE `test_table` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `name` VARCHAR(255) NOT NULL,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
                'params' => [],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }

    public function testExecuteAddColumn()
    {
        $usedQueries = [];
        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'add_column',
                    'table_name' => 'test_table',
                    'field' => [
                        'name' => 'new_column',
                        'type' => 'varchar',
                        'size' => 100,
                        'default' => 'default_value',
                    ],
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                ALTER TABLE `test_table`
                ADD `new_column` VARCHAR(100) NOT NULL DEFAULT 'default_value'
SQL,
                'params' => [],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }

    public function testExecuteAddConstraintUnique()
    {
        $usedQueries = [];
        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'add_constraint',
                    'table_name' => 'test_table',
                    'constraint' => [
                        'type' => 'unique',
                        'values' => ['id'],
                    ],
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                ALTER TABLE `test_table`
                ADD UNIQUE KEY `unique_test_table_id` (`id`)
SQL,
                'params' => [],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }

    public function testExecuteAddConstraintIndex()
    {
        $usedQueries = [];
        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'add_constraint',
                    'table_name' => 'test_table',
                    'constraint' => [
                        'type' => 'index',
                        'name' => 'index_for_id',
                        'values' => ['id'],
                    ],
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                ALTER TABLE `test_table`
                ADD INDEX `index_for_id` (`id`)
SQL,
                'params' => [],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }

    public function testExecuteInsertRow()
    {
        $usedQueries = [];
        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'insert_row',
                    'table_name' => 'test_table',
                    'values' => [
                        'name' => 'John Doe',
                    ],
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                INSERT INTO `test_table` SET `name` = ?
SQL,
                'params' => ['John Doe'],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }

    public function testExecuteModifyColumnType()
    {
        $usedQueries = [];
        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'modify_column_type',
                    'table_name' => 'test_table',
                    'field' => [
                        'name' => 'name',
                        'type' => 'varchar',
                        'size' => 255,
                    ],
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                ALTER TABLE `test_table`
                MODIFY `name` VARCHAR(255) NOT NULL
SQL,
                'params' => [],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }

    public function testExecuteRenameColumn()
    {
        $usedQueries = [];
        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'rename_column',
                    'table_name' => 'test_table',
                    'name' => 'name',
                    'new_name' => 'new_name',
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                ALTER TABLE `test_table`
                RENAME COLUMN `name` TO `new_name`
SQL,
                'params' => [],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }

    public function testExecuteRenameTable()
    {
        $usedQueries = [];
        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'rename_table',
                    'table_name' => 'test_table',
                    'new_name' => 'new_table',
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                ALTER TABLE `test_table`
                RENAME TO `new_table`
SQL,
                'params' => [],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }

    public function testExecuteRawQuery()
    {
        $usedQueries = [];
        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'raw_query',
                    'query' => 'SELECT * FROM `test_table`',
                    'params' => [],
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                SELECT * FROM `test_table`
SQL,
                'params' => [],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }

    public function testExecuteTask()
    {
        $task = $this->makeEmpty(Task::class, [
            'execute' => Expected::once(),
        ]);

        $container = $this->makeEmpty(
            Container::class,
            [
                'get' => Expected::once($task),
            ]
        );

        $database = $this->makeEmpty(
            Database::class,
            [
                'query' => Expected::never(),
                'startTransaction' => Expected::once(),
                'commitTransaction' => Expected::once(),
                'getLastError' => Expected::never(),
            ],
        );

        $manager = new DatabaseManager(
            $database,
            $container,
            fopen('php://memory', 'w')
        );

        $migrationData = [
            'actions' => [
                [
                    'type' => 'run_task',
                    'task' => Task::class,
                ],
            ],
        ];

        $manager->execute($migrationData);
    }

    public function testExecuteDryRun()
    {
        $usedQueryString = '';
        $usedParams = [];

        $database = $this->makeEmpty(
            Database::class,
            [
                'query' => Expected::never(),
                'startTransaction' => Expected::never(),
                'commitTransaction' => Expected::never(),
            ]
        );

        $manager = new DatabaseManager(
            $database,
            $this->makeEmpty(Container::class),
            fopen('php://memory', 'w')
        );

        $migrationData = [
            'actions' => [
                [
                    'type' => 'create_table',
                    'table_name' => 'test_table',
                    'fields' => [
                        [
                            'name' => 'id',
                            'type' => 'int',
                        ],
                    ],
                    'constraints' => [
                        [
                            'type' => 'unique',
                            'values' => ['id'],
                        ],
                    ],
                ],
            ],
        ];

        $manager->execute($migrationData, true);
    }

    public function testInvalidActionType()
    {
        $manager = new DatabaseManager(
            $this->makeEmpty(Database::class),
            $this->makeEmpty(Container::class),
            fopen('php://memory', 'w')
        );

        $migrationData = [
            'actions' => [
                [
                    'type' => 'invalid_action',
                    'table_name' => 'test_table',
                ],
            ],
        ];

        verify(function () use ($manager, $migrationData) {
            $manager->execute($migrationData);
        })->callableThrows(\RuntimeException::class, "Unknown action type 'invalid_action'");
    }

    public function testExecuteWithConstraints()
    {
        $usedQueries = [];
        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'create_table',
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
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                    CREATE TABLE `test_table` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `email` VARCHAR(255) NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `unique_test_table_email` (`email`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
                'params' => [],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }

    public function testExecuteWithDefaultValues()
    {
        $usedQueries = [];
        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'create_table',
                    'table_name' => 'test_table',
                    'fields' => [
                        [
                            'name' => 'status',
                            'type' => 'varchar',
                            'size' => 50,
                            'default' => 'active',
                        ],
                        [
                            'name' => 'created_at',
                            'type' => 'timestamp',
                            'default' => ['function' => 'CURRENT_TIMESTAMP'],
                        ],
                    ],
                    'constraints' => [
                    ],
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                    CREATE TABLE `test_table` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `status` VARCHAR(50) NOT NULL DEFAULT 'active',
                        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
                'params' => [],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }

    public function testExecuteMultipleActions()
    {
        $usedQueries = [];
        $manager = $this->getQuerySaverManager($usedQueries);

        $migrationData = [
            'actions' => [
                [
                    'type' => 'create_table',
                    'table_name' => 'table1',
                    'fields' => [
                    ],
                    'constraints' => [
                    ],
                ],
                [
                    'type' => 'create_table',
                    'table_name' => 'table2',
                    'fields' => [
                    ],
                    'constraints' => [
                    ],
                ],
            ],
        ];

        $manager->execute($migrationData);
        $usedQueries = $this->normalizeQueryStrings($usedQueries);

        $expectedQueries = [
            [
                'query' => <<<'SQL'
                    CREATE TABLE `table1` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
                'params' => [],
            ],
            [
                'query' => <<<'SQL'
                    CREATE TABLE `table2` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
                'params' => [],
            ],
        ];

        $expectedQueries = $this->normalizeQueryStrings($expectedQueries);

        verify($usedQueries)->equals($expectedQueries);
    }
}
