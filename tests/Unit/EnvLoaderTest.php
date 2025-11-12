<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Core\EnvLoader;

/**
 * @internal
 *
 * @covers \WebFramework\Core\EnvLoader
 */
final class EnvLoaderTest extends Unit
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/envloader_test_'.uniqid();
        mkdir($this->tempDir);
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

    public function testLoadEnvFileNotExists()
    {
        $envLoader = new EnvLoader();
        $envLoader->loadEnvFile('/nonexistent/file.env');

        verify(getenv('NONEXISTENT_VAR'))->equals(false);
    }

    public function testLoadEnvFileEmpty()
    {
        $envFile = $this->tempDir.'/.env';
        file_put_contents($envFile, '');

        $envLoader = new EnvLoader();
        $envLoader->loadEnvFile($envFile);

        verify(getenv('NONEXISTENT_VAR'))->equals(false);
    }

    public function testLoadEnvFileBasic()
    {
        $envFile = $this->tempDir.'/.env';
        file_put_contents($envFile, "TEST_VAR=test_value\nANOTHER_VAR=another_value");

        $envLoader = new EnvLoader();
        $envLoader->loadEnvFile($envFile);

        verify(getenv('TEST_VAR'))->equals('test_value');
        verify(getenv('ANOTHER_VAR'))->equals('another_value');

        putenv('TEST_VAR');
        putenv('ANOTHER_VAR');
    }

    public function testLoadEnvFileWithQuotes()
    {
        $envFile = $this->tempDir.'/.env';
        file_put_contents($envFile, 'QUOTED_VAR="quoted value"'."\n".'SINGLE_QUOTED=\'single quoted\'');

        $envLoader = new EnvLoader();
        $envLoader->loadEnvFile($envFile);

        verify(getenv('QUOTED_VAR'))->equals('quoted value');
        verify(getenv('SINGLE_QUOTED'))->equals('single quoted');

        putenv('QUOTED_VAR');
        putenv('SINGLE_QUOTED');
    }

    public function testLoadEnvFileWithComments()
    {
        $envFile = $this->tempDir.'/.env';
        file_put_contents($envFile, "# This is a comment\nVALID_VAR=valid\n# Another comment\nANOTHER_VALID=value");

        $envLoader = new EnvLoader();
        $envLoader->loadEnvFile($envFile);

        verify(getenv('VALID_VAR'))->equals('valid');
        verify(getenv('ANOTHER_VALID'))->equals('value');

        putenv('VALID_VAR');
        putenv('ANOTHER_VALID');
    }

    public function testLoadEnvFilePreservesExisting()
    {
        putenv('EXISTING_VAR=existing_value');

        $envFile = $this->tempDir.'/.env';
        file_put_contents($envFile, 'EXISTING_VAR=new_value');

        $envLoader = new EnvLoader();
        $envLoader->loadEnvFile($envFile);

        verify(getenv('EXISTING_VAR'))->equals('existing_value');

        putenv('EXISTING_VAR');
    }

    public function testLoadEnvFileWithEmptyLines()
    {
        $envFile = $this->tempDir.'/.env';
        file_put_contents($envFile, "VAR1=value1\n\n\nVAR2=value2\n\n");

        $envLoader = new EnvLoader();
        $envLoader->loadEnvFile($envFile);

        verify(getenv('VAR1'))->equals('value1');
        verify(getenv('VAR2'))->equals('value2');

        putenv('VAR1');
        putenv('VAR2');
    }
}
