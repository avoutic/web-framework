<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Config\ConfigBuilder;

/**
 * @internal
 *
 * @covers \WebFramework\Config\ConfigBuilder
 */
final class ConfigBuilderBuildTest extends Unit
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/configbuilder_test_'.uniqid();
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->cleanupTempDir();
        parent::tearDown();
    }

    private function cleanupTempDir(): void
    {
        if (is_dir($this->tempDir))
        {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file)
            {
                if ($file->isDir())
                {
                    rmdir($file->getRealPath());
                }
                else
                {
                    unlink($file->getRealPath());
                }
            }
            rmdir($this->tempDir);
        }
    }

    private function createConfigFile(string $path, array $config): void
    {
        $fullPath = $this->tempDir.$path;
        $dir = dirname($fullPath);
        if (!is_dir($dir))
        {
            mkdir($dir, 0o777, true);
        }
        file_put_contents($fullPath, "<?php\n\nreturn ".var_export($config, true).";\n");
    }

    public function testBuildConfigEmpty()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        $result = $instance->buildConfig([]);

        verify($result)->equals([]);
    }

    public function testBuildConfigSingleFile()
    {
        $this->createConfigFile('/config1.php', [
            'key1' => 'val1',
            'key2' => 'val2',
        ]);

        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        $result = $instance->buildConfig(['/config1.php']);

        verify($result)->equals([
            'key1' => 'val1',
            'key2' => 'val2',
        ]);
    }

    public function testBuildConfigMultipleFiles()
    {
        $this->createConfigFile('/config1.php', [
            'key1' => 'val1',
            'key2' => 'val2',
        ]);

        $this->createConfigFile('/config2.php', [
            'key2' => 'val3',
            'key3' => 'val4',
        ]);

        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        $result = $instance->buildConfig(['/config1.php', '/config2.php']);

        verify($result)->equals([
            'key1' => 'val1',
            'key2' => 'val3',
            'key3' => 'val4',
        ]);
    }

    public function testBuildConfigMultipleFilesDeepMerge()
    {
        $this->createConfigFile('/config1.php', [
            'nested' => [
                'a' => 1,
                'b' => 2,
            ],
        ]);

        $this->createConfigFile('/config2.php', [
            'nested' => [
                'b' => 3,
                'c' => 4,
            ],
        ]);

        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        $result = $instance->buildConfig(['/config1.php', '/config2.php']);

        verify($result)->equals([
            'nested' => [
                'a' => 1,
                'b' => 3,
                'c' => 4,
            ],
        ]);
    }

    public function testBuildConfigOptionalFileExists()
    {
        $this->createConfigFile('/config_local.php', [
            'key1' => 'val1',
        ]);

        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        $result = $instance->buildConfig(['?/config_local.php']);

        verify($result)->equals([
            'key1' => 'val1',
        ]);
    }

    public function testBuildConfigOptionalFileNotExists()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        $result = $instance->buildConfig(['?/config_local.php']);

        verify($result)->equals([]);
    }

    public function testBuildConfigOptionalFileWithOtherFiles()
    {
        $this->createConfigFile('/config1.php', [
            'key1' => 'val1',
        ]);

        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        $result = $instance->buildConfig(['/config1.php', '?/config_local.php']);

        verify($result)->equals([
            'key1' => 'val1',
        ]);
    }

    public function testBuildConfigOptionalFileExistsWithOtherFiles()
    {
        $this->createConfigFile('/config1.php', [
            'key1' => 'val1',
        ]);

        $this->createConfigFile('/config_local.php', [
            'key1' => 'val2',
            'key2' => 'val3',
        ]);

        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        $result = $instance->buildConfig(['/config1.php', '?/config_local.php']);

        verify($result)->equals([
            'key1' => 'val2',
            'key2' => 'val3',
        ]);
    }

    public function testBuildConfigNumericArrayReplacement()
    {
        $this->createConfigFile('/config1.php', [
            'routes' => ['RouteA', 'RouteB'],
        ]);

        $this->createConfigFile('/config2.php', [
            'routes' => ['RouteC'],
        ]);

        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        $result = $instance->buildConfig(['/config1.php', '/config2.php']);

        verify($result)->equals([
            'routes' => ['RouteC'],
        ]);
    }

    public function testBuildConfigComplexRealWorld()
    {
        $this->createConfigFile('/config/base.php', [
            'definition_files' => ['/a.php', '/b.php'],
            'routes' => ['RouteA'],
            'middlewares' => [
                'pre_routing' => ['PreA'],
                'post_routing' => ['PostA'],
            ],
        ]);

        $this->createConfigFile('/config/local.php', [
            'routes' => ['RouteB'],
            'middlewares' => [
                'pre_routing' => ['PreB'],
            ],
            'translations' => [
                'default_language' => 'en',
            ],
        ]);

        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        $result = $instance->buildConfig(['/config/base.php', '/config/local.php']);

        verify($result)->equals([
            'definition_files' => ['/a.php', '/b.php'],
            'routes' => ['RouteB'],
            'middlewares' => [
                'pre_routing' => ['PreB'],
                'post_routing' => ['PostA'],
            ],
            'translations' => [
                'default_language' => 'en',
            ],
        ]);
    }

    public function testBuildConfigFileNotReturnsArray()
    {
        $invalidConfigPath = $this->tempDir.'/invalid.php';
        file_put_contents($invalidConfigPath, "<?php\n\nreturn 'not an array';\n");

        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        verify(function () use ($instance) {
            $instance->buildConfig(['/invalid.php']);
        })->callableThrows(\RuntimeException::class, "No valid config array found in '/invalid.php'");
    }

    public function testBuildConfigRequiredFileNotExists()
    {
        $instance = $this->construct(
            ConfigBuilder::class,
            [
                $this->tempDir,
            ],
        );

        verify(function () use ($instance) {
            $instance->buildConfig(['/nonexistent.php']);
        })->callableThrows(\RuntimeException::class, "File '/nonexistent.php' does not exist");
    }
}
