<?php

namespace Tests\Instantiations;

use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\Middleware\AdminUserMiddleware;
use WebFramework\Middleware\AuthenticationMiddleware;
use WebFramework\Middleware\BlacklistMiddleware;
use WebFramework\Middleware\CsrfValidationMiddleware;
use WebFramework\Middleware\ErrorRedirectMiddleware;
use WebFramework\Middleware\InstrumentationMiddleware;
use WebFramework\Middleware\IpMiddleware;
use WebFramework\Middleware\JsonParserMiddleware;
use WebFramework\Middleware\LoggedInMiddleware;
use WebFramework\Middleware\MessageMiddleware;
use WebFramework\Middleware\RequestServiceMiddleware;
use WebFramework\Middleware\SecurityHeadersMiddleware;
use WebFramework\Middleware\TransactionMiddleware;

class testMiddlewareCest
{
    use TaskRunnerTrait;

    private array $configFiles = [
        '/config/base_config.php',
        '?/config/config_local.php',
        '/tests/config_instantiations.php',
    ];

    private array $classes = [
        AdminUserMiddleware::class,
        AuthenticationMiddleware::class,
        BlacklistMiddleware::class,
        CsrfValidationMiddleware::class,
        ErrorRedirectMiddleware::class,
        InstrumentationMiddleware::class,
        IpMiddleware::class,
        JsonParserMiddleware::class,
        LoggedInMiddleware::class,
        MessageMiddleware::class,
        RequestServiceMiddleware::class,
        SecurityHeadersMiddleware::class,
        TransactionMiddleware::class,
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
