<?php

namespace Tests\Instantiations;

use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\Task\DbMakeMigrationTask;
use WebFramework\Task\DbMigrateFromSchemeTask;
use WebFramework\Task\DbMigrateTask;
use WebFramework\Task\DbStatusTask;
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
        DbMigrateTask::class,
        DbMigrateFromSchemeTask::class,
        DbStatusTask::class,
        DbMakeMigrationTask::class,
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
