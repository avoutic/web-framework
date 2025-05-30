<?php

namespace Tests\Instantiations;

use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\Queue\MemoryQueue;
use WebFramework\Queue\Queue;
use WebFramework\Queue\QueueService;

class testQueueCest
{
    use TaskRunnerTrait;

    private array $configFiles = [
        '/config/base_config.php',
        '?/config/config_local.php',
        '/tests/config_instantiations.php',
    ];

    private array $classes = [
        MemoryQueue::class,
        Queue::class,
        QueueService::class,
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
