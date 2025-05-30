<?php

namespace Tests\Instantiations;

use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\SanityCheck\DatabaseCompatibility;
use WebFramework\SanityCheck\RequiredAuth;
use WebFramework\SanityCheck\RequiredCoreConfig;
use WebFramework\SanityCheck\SanityCheckRunner;

class testSanityCheckCest
{
    use TaskRunnerTrait;

    private array $configFiles = [
        '/config/base_config.php',
        '?/config/config_local.php',
        '/tests/config_instantiations.php',
    ];

    private array $classes = [
        DatabaseCompatibility::class,
        RequiredAuth::class,
        RequiredCoreConfig::class,
        SanityCheckRunner::class,
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
