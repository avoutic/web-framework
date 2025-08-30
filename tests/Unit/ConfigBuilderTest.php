<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Core\ConfigBuilder;

/**
 * @internal
 *
 * @coversNothing
 */
final class ConfigBuilderTest extends Unit
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
            ->equals([])
        ;
    }

    public function testMergingEmptyConfigDoesNotEraseExisting()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'definition_files' => ['/a.php'],
            'routes' => ['R1'],
            'middlewares' => [
                'pre_routing' => ['P'],
                'post_routing' => ['Q'],
            ],
        ]);

        $instance->mergeConfigOnTop([]);

        $config = $instance->getConfig();
        verify($config['definition_files'])->equals(['/a.php']);
        verify($config['routes'])->equals(['R1']);
        verify($config['middlewares']['pre_routing'])->equals(['P']);
        verify($config['middlewares']['post_routing'])->equals(['Q']);
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
            ])
        ;
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
            ])
        ;
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
            ])
        ;
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
            ])
        ;
    }

    public function testReplaceNumericTopLevelDefinitionFiles()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'definition_files' => [
                '/a.php',
                '/b.php',
                '/c.php',
            ],
        ]);

        $instance->mergeConfigOnTop([
            'definition_files' => [
                '/override.php',
            ],
        ]);

        $config = $instance->getConfig();
        verify($config['definition_files'])->equals(['/override.php']);
    }

    public function testEmptyListOverrideErasesDefinitionFiles()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'definition_files' => ['/a.php', '/b.php'],
        ]);

        $instance->mergeConfigOnTop([
            'definition_files' => [],
        ]);

        $config = $instance->getConfig();
        verify($config['definition_files'])->equals([]);
    }

    public function testReplaceNestedMiddlewaresPreRouting()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'middlewares' => [
                'pre_routing' => ['MiddlewareA', 'MiddlewareB'],
                'post_routing' => ['PostA'],
            ],
        ]);

        $instance->mergeConfigOnTop([
            'middlewares' => [
                'pre_routing' => ['OverrideMiddleware'],
            ],
        ]);

        $config = $instance->getConfig();
        verify($config['middlewares']['pre_routing'])->equals(['OverrideMiddleware']);
        verify($config['middlewares']['post_routing'])->equals(['PostA']);
    }

    public function testEmptyListOverrideErasesPreRouting()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'middlewares' => [
                'pre_routing' => ['A', 'B'],
                'post_routing' => ['X'],
            ],
        ]);

        $instance->mergeConfigOnTop([
            'middlewares' => [
                'pre_routing' => [],
            ],
        ]);

        $config = $instance->getConfig();
        verify($config['middlewares']['pre_routing'])->equals([]);
        verify($config['middlewares']['post_routing'])->equals(['X']);
    }

    public function testReplaceTranslationsDirectories()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'translations' => [
                'default_language' => 'en',
                'directories' => ['/dir/a', '/dir/b'],
            ],
        ]);

        $instance->mergeConfigOnTop([
            'translations' => [
                'directories' => ['/dir/override'],
            ],
        ]);

        $config = $instance->getConfig();
        verify($config['translations']['directories'])->equals(['/dir/override']);
        verify($config['translations']['default_language'])->equals('en');
    }

    public function testEmptyListOverrideErasesTranslationsDirectories()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'translations' => [
                'default_language' => 'en',
                'directories' => ['/dir/a', '/dir/b'],
            ],
        ]);

        $instance->mergeConfigOnTop([
            'translations' => [
                'directories' => [],
            ],
        ]);

        $config = $instance->getConfig();
        verify($config['translations']['directories'])->equals([]);
        verify($config['translations']['default_language'])->equals('en');
    }

    public function testAssociativeDeepMerge()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'a' => [
                'b' => 1,
                'c' => 2,
                'd' => [
                    'x' => 10,
                    'y' => 20,
                ],
            ],
        ]);

        $instance->mergeConfigOnTop([
            'a' => [
                'b' => 3,
                'd' => [
                    'y' => 25,
                    'z' => 30,
                ],
            ],
        ]);

        $config = $instance->getConfig();
        verify($config['a']['b'])->equals(3);
        verify($config['a']['c'])->equals(2);
        verify($config['a']['d']['x'])->equals(10);
        verify($config['a']['d']['y'])->equals(25);
        verify($config['a']['d']['z'])->equals(30);
    }

    public function testReplaceRoutesList()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'routes' => ['RouteA', 'RouteB'],
        ]);

        $instance->mergeConfigOnTop([
            'routes' => ['RouteOverride'],
        ]);

        $config = $instance->getConfig();
        verify($config['routes'])->equals(['RouteOverride']);
    }

    public function testEmptyListOverrideErasesRoutes()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'routes' => ['RouteA', 'RouteB'],
        ]);

        $instance->mergeConfigOnTop([
            'routes' => [],
        ]);

        $config = $instance->getConfig();
        verify($config['routes'])->equals([]);
    }

    public function testReplaceNestedMiddlewaresPostRouting()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'middlewares' => [
                'pre_routing' => ['PreA'],
                'post_routing' => ['PostA', 'PostB'],
            ],
        ]);

        $instance->mergeConfigOnTop([
            'middlewares' => [
                'post_routing' => ['PostOverride'],
            ],
        ]);

        $config = $instance->getConfig();
        verify($config['middlewares']['pre_routing'])->equals(['PreA']);
        verify($config['middlewares']['post_routing'])->equals(['PostOverride']);
    }

    public function testEmptyListOverrideErasesPostRouting()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                '/appdir',
            ],
        );

        $instance->mergeConfigOnTop([
            'middlewares' => [
                'pre_routing' => ['PreA'],
                'post_routing' => ['PostA', 'PostB'],
            ],
        ]);

        $instance->mergeConfigOnTop([
            'middlewares' => [
                'post_routing' => [],
            ],
        ]);

        $config = $instance->getConfig();
        verify($config['middlewares']['post_routing'])->equals([]);
        verify($config['middlewares']['pre_routing'])->equals(['PreA']);
    }
}
