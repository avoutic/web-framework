<?php

namespace Tests\Support;

use WebFramework\Task\TaskRunner;

/**
 * @internal
 *
 * @covers \WebFramework\Task\TaskRunner
 */
trait TaskRunnerTrait
{
    private TaskRunner $taskRunner;
    protected function instantiateTaskRunner(array $configFiles = [])
    {
        $this->taskRunner = new TaskRunner(
            appDir: __DIR__.'/../..',
        );
        if (!empty($configFiles))
        {
            $this->taskRunner->setConfigFiles($configFiles);
        }
        $this->taskRunner->build();
    }

    protected function get(string $class)
    {
        return $this->taskRunner->get($class);
    }
}
