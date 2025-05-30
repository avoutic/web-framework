<?php

namespace Tests\Instantiations;

use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\Validation\InputValidationService;
use WebFramework\Validation\UploadValidationService;

class testValidationCest
{
    use TaskRunnerTrait;

    private array $configFiles = [
        '/config/base_config.php',
        '?/config/config_local.php',
        '/tests/config_instantiations.php',
    ];

    private array $classes = [
        InputValidationService::class,
        UploadValidationService::class,
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
