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
            )
                ->equals([
                    'query' => '`key1` >= ?',
                    'params' => [
                        'val1',
                    ],
                ])
            ;
        })
            ->callableThrows(\RuntimeException::class, 'Invalid filter definition')
        ;
    }
}
