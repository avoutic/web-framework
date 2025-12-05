<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use WebFramework\Database\Database;
use WebFramework\Repository\Column;
use WebFramework\Repository\UserRepository;

/**
 * @internal
 *
 * @covers \WebFramework\Repository\RepositoryCore
 */
final class RepositoryCoreTest extends Unit
{
    public function testGetFilterArrayEmpty()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
            ]
        ))
            ->equals([
                'query' => '',
                'params' => [],
            ])
        ;
    }

    public function testGetFilterArrayNull()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => null,
            ]
        ))
            ->equals([
                'query' => '`key1` IS NULL',
                'params' => [],
            ])
        ;
    }

    public function testGetFilterArrayFalse()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => false,
            ]
        ))
            ->equals([
                'query' => '`key1` = ?',
                'params' => [
                    0,
                ],
            ])
        ;
    }

    public function testGetFilterArrayString()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => 'val1',
            ]
        ))
            ->equals([
                'query' => '`key1` = ?',
                'params' => [
                    'val1',
                ],
            ])
        ;
    }

    public function testGetFilterArrayMultiple()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => 'val1',
                'key2' => 'val2',
            ]
        ))
            ->equals([
                'query' => '`key1` = ? AND `key2` = ?',
                'params' => [
                    'val1',
                    'val2',
                ],
            ])
        ;
    }

    public function testGetFilterArrayAdvancedOperator()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => ['>=', 'val1'],
            ]
        ))
            ->equals([
                'query' => '`key1` >= ?',
                'params' => [
                    'val1',
                ],
            ])
        ;
    }

    public function testGetFilterArrayAdvancedOperatorEqualsNull()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => ['=', null],
            ]
        ))
            ->equals([
                'query' => '`key1` IS NULL',
                'params' => [
                ],
            ])
        ;
    }

    public function testGetFilterArrayAdvancedOperatorNotEqualsNull()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => ['!=', null],
            ]
        ))
            ->equals([
                'query' => '`key1` IS NOT NULL',
                'params' => [
                ],
            ])
        ;
    }

    public function testGetFilterArrayAdvancedOperatorBetween()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => ['BETWEEN', 'val1', 'val2'],
            ]
        ))
            ->equals([
                'query' => '`key1` BETWEEN ? AND ?',
                'params' => [
                    'val1',
                    'val2',
                ],
            ])
        ;
    }

    public function testGetFilterArrayAdvancedOperatorNotBetween()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => ['NOT BETWEEN', 'val1', 'val2'],
            ]
        ))
            ->equals([
                'query' => '`key1` NOT BETWEEN ? AND ?',
                'params' => [
                    'val1',
                    'val2',
                ],
            ])
        ;
    }

    public function testGetFilterArrayAdvancedOperatorIn()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => ['IN', ['val1', 'val2']],
            ]
        ))
            ->equals([
                'query' => '`key1` IN (?, ?)',
                'params' => [
                    'val1',
                    'val2',
                ],
            ])
        ;
    }

    public function testGetFilterArrayAdvancedOperatorNotIn()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => ['NOT IN', ['val1', 'val2']],
            ]
        ))
            ->equals([
                'query' => '`key1` NOT IN (?, ?)',
                'params' => [
                    'val1',
                    'val2',
                ],
            ])
        ;
    }

    public function testGetFilterArrayIllegalAdvancedOperator()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify(function () use ($instance) {
            $instance->getFilterArray(
                [
                    'key1' => [],
                ]
            );
        })
            ->callableThrows(\RuntimeException::class, 'Invalid filter definition')
        ;
    }

    public function testGetFilterArrayColumnComparison()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => new Column('key2'),
            ]
        ))
            ->equals([
                'query' => '`key1` = `key2`',
                'params' => [],
            ])
        ;
    }

    public function testGetFilterArrayColumnComparisonWithOperator()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => ['>', new Column('key2')],
            ]
        ))
            ->equals([
                'query' => '`key1` > `key2`',
                'params' => [],
            ])
        ;
    }

    public function testGetFilterArrayOr()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'OR' => [
                    ['key1' => 'val1'],
                    ['key2' => 'val2'],
                ],
            ]
        ))
            ->equals([
                'query' => '(`key1` = ? OR `key2` = ?)',
                'params' => [
                    'val1',
                    'val2',
                ],
            ])
        ;
    }

    public function testGetFilterArrayMultipleConditions()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => [
                    ['!=', null],
                    ['<', 'val1'],
                ],
            ]
        ))
            ->equals([
                'query' => '`key1` IS NOT NULL AND `key1` < ?',
                'params' => [
                    'val1',
                ],
            ])
        ;
    }

    public function testGetFilterArrayMultipleConditionsNull()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => [
                    null,
                    ['<', 'val1'],
                ],
            ]
        ))
            ->equals([
                'query' => '`key1` IS NULL AND `key1` < ?',
                'params' => [
                    'val1',
                ],
            ])
        ;
    }

    public function testGetFilterArrayMultipleConditionsColumn()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => [
                    null,
                    new Column('key2'),
                    ['<', new Column('key3')],
                ],
            ]
        ))
            ->equals([
                'query' => '`key1` IS NULL AND `key1` = `key2` AND `key1` < `key3`',
                'params' => [],
            ])
        ;
    }

    public function testGetFilterArrayMultiConditionsOrNested()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'key1' => [
                    ['!=', new Column('key2')],
                    'OR' => [
                        'key1.1' => 'val1_1',
                        'key1.2' => 'val1_2',
                    ],
                    null,
                ],
            ]
        ))
            ->equals([
                'query' => '`key1` != `key2` AND (`key1.1` = ? OR `key1.2` = ?) AND `key1` IS NULL',
                'params' => [
                    'val1_1',
                    'val1_2',
                ],
            ])
        ;
    }

    public function testGetFilterArrayOrMultiple()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'OR' => [
                    'key1' => [
                        ['=', 'val1'],
                        null,
                    ],
                    'key2' => [
                        'teststring',
                        ['BETWEEN', 'val2', 'val3'],
                    ],
                    'key3' => [
                        ['IN', ['val4', 'val5']],
                        ['NOT IN', ['val6', 'val7']],
                    ],
                ],
            ]
        ))
            ->equals([
                'query' => '((`key1` = ? AND `key1` IS NULL) OR (`key2` = ? AND `key2` BETWEEN ? AND ?) OR (`key3` IN (?, ?) AND `key3` NOT IN (?, ?)))',
                'params' => [
                    'val1',
                    'teststring',
                    'val2',
                    'val3',
                    'val4',
                    'val5',
                    'val6',
                    'val7',
                ],
            ])
        ;
    }

    public function testGetFilterArrayOrOrNested()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify($instance->getFilterArray(
            [
                'OR' => [
                    'key1' => [
                        'OR' => [
                            'key1.1' => 'val1_1',
                            'key1.2' => 'val1_2',
                        ],
                        null,
                    ],
                    'key2' => [
                        'OR' => [
                            'key2.1' => 'val2_1',
                            'key2.2' => 'val2_2',
                        ],
                    ],
                    'key3' => [
                        ['!=', null],
                        'OR' => [
                            'key3.1' => 'val3_1',
                            'val3_2',
                        ],
                    ],
                    'key4' => 15,
                    'key5' => null,
                    'key6' => ['BETWEEN', new Column('key7'), new Column('key8')],
                ],
            ]
        ))
            ->equals([
                'query' => '(((`key1.1` = ? OR `key1.2` = ?) AND `key1` IS NULL) OR ((`key2.1` = ? OR `key2.2` = ?)) OR (`key3` IS NOT NULL AND (`key3.1` = ? OR `key3` = ?)) OR `key4` = ? OR `key5` IS NULL OR (`key6` BETWEEN `key7` AND `key8`))',
                'params' => [
                    'val1_1',
                    'val1_2',
                    'val2_1',
                    'val2_2',
                    'val3_1',
                    'val3_2',
                    15,
                ],
            ])
        ;
    }

    public function testInstantiateEntityFromDataWithPrefix()
    {
        $instance = $this->construct(
            UserRepository::class,
            [
                $this->makeEmpty(Container::class),
                $this->makeEmpty(Database::class),
            ]
        );

        $row = [
            'id' => 1,
            'username' => 'No use',
            'email' => 'no@example.com',
            'u.id' => 42,
            'u.username' => 'tester',
            'u.email' => 'tester@example.com',
        ];

        $entity = $instance->instantiateEntityFromData($row, 'u');

        verify($entity->getId())
            ->equals(42)
        ;

        verify($entity->getUsername())
            ->equals('tester')
        ;

        verify($entity->getEmail())
            ->equals('tester@example.com')
        ;

        verify($entity->getOriginalValues())
            ->equals([
                'id' => 42,
                'username' => 'tester',
                'email' => 'tester@example.com',
            ])
        ;
    }

    public function testInstantiateEntityFromDataWithoutPrefix()
    {
        $instance = $this->construct(
            UserRepository::class,
            [
                $this->makeEmpty(Container::class),
                $this->makeEmpty(Database::class),
            ]
        );

        $row = [
            'id' => 42,
            'username' => 'tester',
            'email' => 'tester@example.com',
            'u.id' => 1,
            'u.username' => 'No use',
            'u.email' => 'no@example.com',
        ];

        $entity = $instance->instantiateEntityFromData($row);

        verify($entity->getId())
            ->equals(42)
        ;

        verify($entity->getUsername())
            ->equals('tester')
        ;

        verify($entity->getEmail())
            ->equals('tester@example.com')
        ;

        verify($entity->getOriginalValues())
            ->equals([
                'id' => 42,
                'username' => 'tester',
                'email' => 'tester@example.com',
            ])
        ;
    }
}
