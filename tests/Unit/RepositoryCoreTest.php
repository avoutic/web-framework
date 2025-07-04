<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Repository\UserRepository;

/**
 * @internal
 *
 * @coversNothing
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

    public function testGetFilterArrayAdvancedOperatorUnknown2ValueOperator()
    {
        $instance = $this->make(
            UserRepository::class,
        );

        verify(function () use ($instance) {
            $instance->getFilterArray(
                [
                    'key1' => ['UNKNOWN', 'val1', 'val2'],
                ]
            );
        })
            ->callableThrows(\RuntimeException::class, 'Invalid filter definition')
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
}
