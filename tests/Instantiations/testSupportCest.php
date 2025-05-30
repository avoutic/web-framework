<?php

namespace Tests\Instantiations;

use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\Support\Base62;
use WebFramework\Support\ContainerWrapper;
use WebFramework\Support\ErrorReport;
use WebFramework\Support\GuzzleClientFactory;
use WebFramework\Support\Helpers;
use WebFramework\Support\Image;
use WebFramework\Support\RequestService;
use WebFramework\Support\StoredUserValuesService;
use WebFramework\Support\StoredValuesService;
use WebFramework\Support\UploadHandler;
use WebFramework\Support\UrlBuilder;
use WebFramework\Support\UuidProvider;
use WebFramework\Support\ValidatorService;
use WebFramework\Support\Webhook;

class testSupportCest
{
    use TaskRunnerTrait;

    private array $configFiles = [
        '/config/base_config.php',
        '?/config/config_local.php',
        '/tests/config_instantiations.php',
    ];

    private array $classes = [
        Base62::class,
        ContainerWrapper::class,
        ErrorReport::class,
        GuzzleClientFactory::class,
        Helpers::class,
        Image::class,
        RequestService::class,
        StoredUserValuesService::class,
        StoredValuesService::class,
        UploadHandler::class,
        UrlBuilder::class,
        UuidProvider::class,
        ValidatorService::class,
        Webhook::class,
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
