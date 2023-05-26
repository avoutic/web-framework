<?php

namespace Tests\Unit;

use WebFramework\Core\ConfigBuilder;

/**
 * @internal
 *
 * @coversNothing
 */
final class ConfigBuilderTest extends \Codeception\Test\Unit
{
    public function testMergeConfigEmpty()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->mergeConfigOnTop([]);

        verify($instance->getConfig())
            ->equals([]);
    }

    public function testMergeConfigSeparate()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->mergeConfigOnTop([
            'key1' => 'val1',
        ]);

        $instance->mergeConfigOnTop([
            'key2' => 'val2',
        ]);

        verify($instance->getConfig())
            ->equals([
                'key1' => 'val1',
                'key2' => 'val2',
            ]);
    }

    public function testMergeConfigSeparateDeep()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->mergeConfigOnTop([
            'key1' => [
                'key1.1' => 'val1.1',
            ],
        ]);

        $instance->mergeConfigOnTop([
            'key1' => [
                'key1.2' => 'val1.2',
            ],
        ]);

        verify($instance->getConfig())
            ->equals([
                'key1' => [
                    'key1.1' => 'val1.1',
                    'key1.2' => 'val1.2',
                ],
            ]);
    }

    public function testMergeConfigOverwrite()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->mergeConfigOnTop([
            'key1' => 'val1',
            'key2' => 'val2',
        ]);

        $instance->mergeConfigOnTop([
            'key2' => 'val3',
        ]);

        verify($instance->getConfig())
            ->equals([
                'key1' => 'val1',
                'key2' => 'val3',
            ]);
    }

    public function testMergeConfigOverwriteDeep()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir/auth',
            ],
        );

        $instance->mergeConfigOnTop([
            'key1' => [
                'key1.1' => 'val1.1',
                'key1.2' => 'val1.2',
            ],
        ]);

        $instance->mergeConfigOnTop([
            'key1' => [
                'key1.2' => 'val1.3',
            ],
        ]);

        verify($instance->getConfig())
            ->equals([
                'key1' => [
                    'key1.1' => 'val1.1',
                    'key1.2' => 'val1.3',
                ],
            ]);
    }
}
