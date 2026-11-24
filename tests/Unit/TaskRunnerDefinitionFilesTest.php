<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Task\TaskRunner;

/**
 * @internal
 *
 * @covers \WebFramework\Task\TaskRunner
 */
final class TaskRunnerDefinitionFilesTest extends Unit
{
    private string $appDir;

    protected function _before(): void
    {
        $this->appDir = codecept_output_dir().'definition_files_app';

        foreach (['/definitions', '/vendor/avoutic/web-framework/definitions'] as $dir)
        {
            if (!is_dir($this->appDir.$dir))
            {
                mkdir($this->appDir.$dir, 0o777, true);
            }
        }

        file_put_contents($this->appDir.'/definitions/app_definitions.php', '<?php return [];');
        file_put_contents($this->appDir.'/vendor/avoutic/web-framework/definitions/definitions.php', '<?php return [];');
    }

    protected function _after(): void
    {
        unlink($this->appDir.'/definitions/app_definitions.php');
        unlink($this->appDir.'/vendor/avoutic/web-framework/definitions/definitions.php');
        rmdir($this->appDir.'/definitions');
        rmdir($this->appDir.'/vendor/avoutic/web-framework/definitions');
        rmdir($this->appDir.'/vendor/avoutic/web-framework');
        rmdir($this->appDir.'/vendor/avoutic');
        rmdir($this->appDir.'/vendor');
        rmdir($this->appDir);
    }

    private function resolve(string $file): ?string
    {
        return (new TaskRunner($this->appDir))->resolveDefinitionFile($file);
    }

    public function testLeadingSlashIsRelativeToTheAppDirectory()
    {
        verify($this->resolve('/vendor/avoutic/web-framework/definitions/definitions.php'))
            ->equals($this->appDir.'/vendor/avoutic/web-framework/definitions/definitions.php')
        ;
        verify($this->resolve('/definitions/app_definitions.php'))
            ->equals($this->appDir.'/definitions/app_definitions.php')
        ;
    }

    public function testOtherPathsAreRelativeToTheDefinitionsDirectory()
    {
        verify($this->resolve('app_definitions.php'))
            ->equals($this->appDir.'/definitions/app_definitions.php')
        ;
        verify($this->resolve('../vendor/avoutic/web-framework/definitions/definitions.php'))
            ->equals($this->appDir.'/definitions/../vendor/avoutic/web-framework/definitions/definitions.php')
        ;
    }

    public function testLeadingSlashFallsBackToTheDefinitionsDirectory()
    {
        // Worked before by accident, as definitions//app_definitions.php
        verify($this->resolve('/app_definitions.php'))
            ->equals($this->appDir.'/definitions/app_definitions.php')
        ;
    }

    public function testOptionalFiles()
    {
        verify($this->resolve('?/definitions/app_definitions.php'))
            ->equals($this->appDir.'/definitions/app_definitions.php')
        ;
        verify($this->resolve('?app_definitions.php'))
            ->equals($this->appDir.'/definitions/app_definitions.php')
        ;
        verify($this->resolve('?/definitions/local_definitions.php'))->null();
        verify($this->resolve('?local_definitions.php'))->null();
    }

    public function testMissingRequiredFileKeepsItsPathSoTheErrorNamesIt()
    {
        verify($this->resolve('/definitions/missing.php'))
            ->equals($this->appDir.'/definitions/missing.php')
        ;
        verify($this->resolve('missing.php'))
            ->equals($this->appDir.'/definitions/missing.php')
        ;
    }
}
