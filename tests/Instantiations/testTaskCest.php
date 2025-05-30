<?php

namespace Tests\Instantiations;

use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\Task\DbInitTask;
use WebFramework\Task\DbUpdateTask;
use WebFramework\Task\DbVersionTask;
use WebFramework\Task\QueueList;
use WebFramework\Task\QueueWorker;
use WebFramework\Task\SanityCheckTask;
use WebFramework\Task\SlimAppTask;

class testTaskCest
{
    use TaskRunnerTrait;

    private array $configFiles = [
        '/config/base_config.php',
        '?/config/config_local.php',
        '/tests/config_instantiations.php',
    ];

    private array $classes = [
        DbInitTask::class,
        DbUpdateTask::class,
        DbVersionTask::class,
        QueueList::class,
        QueueWorker::class,
        SanityCheckTask::class,
        SlimAppTask::class,
    ];

    public function _before(InstantiationTester $I)
    {
        $this->instantiateTaskRunner($this->configFiles);
    }

    // tests
    public function instantiations(InstantiationTester $I)
    {
        foreach ($this->classes as $class)
        {
            $this->get($class);
        }
    }
}
