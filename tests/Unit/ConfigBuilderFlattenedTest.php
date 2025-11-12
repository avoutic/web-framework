<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Config\ConfigBuilder;

/**
 * @internal
 *
 * @covers \WebFramework\Config\ConfigBuilder
 */
final class ConfigBuilderFlattenedTest extends Unit
{
    public function testGetFlattenedConfigEmpty()
    {
        $instance = $this->make(ConfigBuilder::class);

        verify($instance->getFlattenedConfig())
            ->equals([])
        ;
    }

    public function testGetFlattenedConfigFlat()
    {
        $instance = $this->make(ConfigBuilder::class);

        $instance->mergeConfigOnTop([
            'key1' => 'val1',
            'key2' => 'val2',
        ]);

        verify($instance->getFlattenedConfig())
            ->equals([
                'key1' => 'val1',
                'key2' => 'val2',
            ])
        ;
    }

    public function testGetFlattenedConfigSingleLevel()
    {
        $instance = $this->make(ConfigBuilder::class);

        $instance->mergeConfigOnTop([
            'service' => [
                'host' => 'localhost',
                'port' => 8080,
            ],
        ]);

        verify($instance->getFlattenedConfig())
            ->equals([
                'service.host' => 'localhost',
                'service.port' => 8080,
            ])
        ;
    }

    public function testGetFlattenedConfigMultipleLevels()
    {
        $instance = $this->make(ConfigBuilder::class);

        $instance->mergeConfigOnTop([
            'a' => [
                'b' => [
                    'c' => 'value',
                ],
            ],
        ]);

        verify($instance->getFlattenedConfig())
            ->equals([
                'a.b.c' => 'value',
            ])
        ;
    }

    public function testGetFlattenedConfigMixedNesting()
    {
        $instance = $this->make(ConfigBuilder::class);

        $instance->mergeConfigOnTop([
            'key1' => 'val1',
            'nested' => [
                'a' => 1,
                'b' => [
                    'c' => 2,
                ],
            ],
        ]);

        verify($instance->getFlattenedConfig())
            ->equals([
                'key1' => 'val1',
                'nested.a' => 1,
                'nested.b.c' => 2,
            ])
        ;
    }

    public function testGetFlattenedConfigNumericArrays()
    {
        $instance = $this->make(ConfigBuilder::class);

        $instance->mergeConfigOnTop([
            'items' => ['a', 'b', 'c'],
        ]);

        verify($instance->getFlattenedConfig())
            ->equals([
                'items.0' => 'a',
                'items.1' => 'b',
                'items.2' => 'c',
            ])
        ;
    }

    public function testGetFlattenedConfigNestedNumericArrays()
    {
        $instance = $this->make(ConfigBuilder::class);

        $instance->mergeConfigOnTop([
            'config' => [
                'ports' => [80, 443],
            ],
        ]);

        verify($instance->getFlattenedConfig())
            ->equals([
                'config.ports.0' => 80,
                'config.ports.1' => 443,
            ])
        ;
    }

    public function testGetFlattenedConfigMixedAssociativeAndNumeric()
    {
        $instance = $this->make(ConfigBuilder::class);

        $instance->mergeConfigOnTop([
            'middlewares' => [
                'pre_routing' => ['A', 'B'],
                'post_routing' => ['C'],
            ],
        ]);

        verify($instance->getFlattenedConfig())
            ->equals([
                'middlewares.pre_routing.0' => 'A',
                'middlewares.pre_routing.1' => 'B',
                'middlewares.post_routing.0' => 'C',
            ])
        ;
    }

    public function testGetFlattenedConfigComplexRealWorld()
    {
        $instance = $this->make(ConfigBuilder::class);

        $instance->mergeConfigOnTop([
            'definition_files' => ['/a.php', '/b.php'],
            'routes' => ['RouteA', 'RouteB'],
            'middlewares' => [
                'pre_routing' => ['MiddlewareA', 'MiddlewareB'],
                'post_routing' => ['PostA'],
            ],
            'translations' => [
                'default_language' => 'en',
                'directories' => ['/dir/a', '/dir/b'],
            ],
        ]);

        verify($instance->getFlattenedConfig())
            ->equals([
                'definition_files.0' => '/a.php',
                'definition_files.1' => '/b.php',
                'routes.0' => 'RouteA',
                'routes.1' => 'RouteB',
                'middlewares.pre_routing.0' => 'MiddlewareA',
                'middlewares.pre_routing.1' => 'MiddlewareB',
                'middlewares.post_routing.0' => 'PostA',
                'translations.default_language' => 'en',
                'translations.directories.0' => '/dir/a',
                'translations.directories.1' => '/dir/b',
            ])
        ;
    }

    public function testGetFlattenedConfigAfterMerging()
    {
        $instance = $this->make(ConfigBuilder::class);

        $instance->mergeConfigOnTop([
            'key1' => 'val1',
            'nested' => [
                'a' => 1,
            ],
        ]);

        $instance->mergeConfigOnTop([
            'nested' => [
                'b' => 2,
            ],
        ]);

        verify($instance->getFlattenedConfig())
            ->equals([
                'key1' => 'val1',
                'nested.a' => 1,
                'nested.b' => 2,
            ])
        ;
    }
}
