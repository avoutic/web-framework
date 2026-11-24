<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Task\TaskRunner;

/**
 * @internal
 *
 * @covers \WebFramework\Task\TaskRunner
 */
final class TaskRunnerConfigFilesTest extends Unit
{
    private false|string $originalAppEnv;

    protected function _before(): void
    {
        $this->originalAppEnv = getenv('APP_ENV');
    }

    protected function _after(): void
    {
        ($this->originalAppEnv === false) ? putenv('APP_ENV') : putenv('APP_ENV='.$this->originalAppEnv);
    }

    public function testDefaultFilesWithoutAppEnv()
    {
        putenv('APP_ENV');

        verify((new TaskRunner('/app'))->getConfigFiles())->same([
            '/vendor/avoutic/web-framework/config/base_config.php',
            '/config/config.php',
            '?/config/config_local.php',
        ]);
    }

    public function testEnvironmentFileIsLoadedBetweenConfigAndLocal()
    {
        putenv('APP_ENV=staging');

        verify((new TaskRunner('/app'))->getConfigFiles())->same([
            '/vendor/avoutic/web-framework/config/base_config.php',
            '/config/config.php',
            '?/config/config_staging.php',
            '?/config/config_local.php',
        ]);
    }

    public function testLocalEnvironmentDoesNotLoadConfigLocalTwice()
    {
        putenv('APP_ENV=local');

        verify((new TaskRunner('/app'))->getConfigFiles())->same([
            '/vendor/avoutic/web-framework/config/base_config.php',
            '/config/config.php',
            '?/config/config_local.php',
        ]);
    }

    public function testUnsafeEnvironmentNameIsIgnored()
    {
        putenv('APP_ENV=../../etc/passwd');

        verify((new TaskRunner('/app'))->getConfigFiles())->same([
            '/vendor/avoutic/web-framework/config/base_config.php',
            '/config/config.php',
            '?/config/config_local.php',
        ]);
    }

    public function testExplicitFilesAreUsedAsGiven()
    {
        putenv('APP_ENV=staging');

        $taskRunner = new TaskRunner('/app');
        $taskRunner->setConfigFiles(['/config/only.php']);

        verify($taskRunner->getConfigFiles())->same(['/config/only.php']);
    }
}
