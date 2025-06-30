<?php

namespace Tests\Instantiations;

use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\Core\BootstrapService;
use WebFramework\Core\BuildInfoService;
use WebFramework\Core\Cache;
use WebFramework\Core\CaptchaService;
use WebFramework\Core\ConfigService;
use WebFramework\Core\ConsoleTaskRegistryService;
use WebFramework\Core\Database;
use WebFramework\Core\DatabaseManager;
use WebFramework\Core\DatabaseProvider;
use WebFramework\Core\DebugService;
use WebFramework\Core\Instrumentation;
use WebFramework\Core\LatteRenderService;
use WebFramework\Core\MailReportFunction;
use WebFramework\Core\MailService;
use WebFramework\Core\MessageService;
use WebFramework\Core\MiddlewareRegistrar;
use WebFramework\Core\NullCache;
use WebFramework\Core\NullDatabase;
use WebFramework\Core\NullInstrumentation;
use WebFramework\Core\NullMailService;
use WebFramework\Core\NullReportFunction;
use WebFramework\Core\QueuedMailService;
use WebFramework\Core\Recaptcha;
use WebFramework\Core\RecaptchaFactory;
use WebFramework\Core\RenderService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Core\RouteRegistrar;
use WebFramework\Core\RuntimeEnvironment;
use WebFramework\Core\UserMailer;
use WebFramework\Core\UserService;

class testCoreCest
{
    use TaskRunnerTrait;

    private array $configFiles = [
        '/config/base_config.php',
        '?/config/config_local.php',
        '/tests/config_instantiations.php',
    ];

    private array $classes = [
        BootstrapService::class,
        BuildInfoService::class,
        Cache::class,
        CaptchaService::class,
        ConfigService::class,
        ConsoleTaskRegistryService::class,
        Database::class,
        DatabaseManager::class,
        DatabaseProvider::class,
        DebugService::class,
        Instrumentation::class,
        LatteRenderService::class,
        MailReportFunction::class,
        MailService::class,
        MessageService::class,
        MiddlewareRegistrar::class,
        NullCache::class,
        NullDatabase::class,
        NullInstrumentation::class,
        NullMailService::class,
        NullReportFunction::class,
        QueuedMailService::class,
        Recaptcha::class,
        RecaptchaFactory::class,
        RenderService::class,
        ResponseEmitter::class,
        RouteRegistrar::class,
        RuntimeEnvironment::class,
        UserMailer::class,
        UserService::class,
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
