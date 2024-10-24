<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Core\RepositoryCore;

/**
 * @internal
 *
 * @coversNothing
 */
final class RepositoryCoreTest extends Unit
{
    public function testMgetFilterArrayEmpty()
    {
        $instance = $this->make(
            RepositoryCore::class,
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

    public function testMgetFilterArrayNull()
    {
        $instance = $this->make(
            RepositoryCore::class,
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

    public function testMgetFilterArrayFalse()
    {
        $instance = $this->make(
            RepositoryCore::class,
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

    public function testMgetFilterArrayString()
    {
        $instance = $this->make(
            RepositoryCore::class,
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

    public function testMgetFilterArrayMultiple()
    {
        $instance = $this->make(
            RepositoryCore::class,
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

    public function testMgetFilterArrayAdvancedOperator()
    {
        $instance = $this->make(
            RepositoryCore::class,
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

    public function testMgetFilterArrayIllegalAdvancedOperator()
    {
        $instance = $this->make(
            RepositoryCore::class,
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
