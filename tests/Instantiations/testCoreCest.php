<?php

namespace Tests\Instantiations;

use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\Cache\Cache;
use WebFramework\Cache\NullCache;
use WebFramework\Config\ConfigService;
use WebFramework\Console\ConsoleTaskRegistryService;
use WebFramework\Core\BootstrapService;
use WebFramework\Core\BuildInfoService;
use WebFramework\Core\RuntimeEnvironment;
use WebFramework\Core\UserService;
use WebFramework\Database\Database;
use WebFramework\Database\DatabaseProvider;
use WebFramework\Database\NullDatabase;
use WebFramework\Diagnostics\DebugService;
use WebFramework\Diagnostics\Instrumentation;
use WebFramework\Diagnostics\MailReportFunction;
use WebFramework\Diagnostics\NullInstrumentation;
use WebFramework\Diagnostics\NullReportFunction;
use WebFramework\Http\MiddlewareRegistrar;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Http\RouteRegistrar;
use WebFramework\Mail\MailService;
use WebFramework\Mail\NullMailService;
use WebFramework\Mail\QueuedMailService;
use WebFramework\Mail\UserMailer;
use WebFramework\Migration\DatabaseManager;
use WebFramework\Presentation\LatteRenderService;
use WebFramework\Presentation\MessageService;
use WebFramework\Presentation\RenderService;
use WebFramework\Security\CaptchaService;
use WebFramework\Security\Recaptcha;
use WebFramework\Security\RecaptchaFactory;

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
