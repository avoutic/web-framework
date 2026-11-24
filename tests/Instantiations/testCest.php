<?php

namespace Tests\Instantiations;

use Psr\Container\ContainerInterface;
use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\Config\ConfigBuilder;
use WebFramework\Database\DatabaseResultWrapper;
use WebFramework\Database\Lock;
use WebFramework\Event\JobCompleted;
use WebFramework\Event\JobFailed;
use WebFramework\Event\UserEmailChanged;
use WebFramework\Event\UserLoggedIn;
use WebFramework\Event\UserPasswordChanged;
use WebFramework\Event\UserRegistered;
use WebFramework\Event\UserVerified;
use WebFramework\Migration\QueryStep;
use WebFramework\Migration\TaskStep;
use WebFramework\Queue\DatabaseQueue;
use WebFramework\Repository\Column;
use WebFramework\Repository\RepositoryQuery;
use WebFramework\Task\TaskArgument;
use WebFramework\Task\TaskOption;
use WebFramework\Testing\InstantiationSweep;

class testCest
{
    use TaskRunnerTrait;

    private array $configFiles = [
        '/config/base_config.php',
        '?/config/config_local.php',
        '/tests/config_instantiations.php',
    ];

    private array $namespaces = [
        'WebFramework\Actions',
        'WebFramework\Cache',
        'WebFramework\Config',
        'WebFramework\Console',
        'WebFramework\Core',
        'WebFramework\Database',
        'WebFramework\Diagnostics',
        'WebFramework\Event',
        'WebFramework\Handler',
        'WebFramework\Http',
        'WebFramework\Logging',
        'WebFramework\Mail',
        'WebFramework\Middleware',
        'WebFramework\Migration',
        'WebFramework\Presentation',
        'WebFramework\Queue',
        'WebFramework\Repository',
        'WebFramework\SanityCheck',
        'WebFramework\Security',
        'WebFramework\Support',
        'WebFramework\Task',
        'WebFramework\Translation',
        'WebFramework\Validation',
    ];

    private array $skippedClasses = [
        // Config
        ConfigBuilder::class,
        // Database
        DatabaseResultWrapper::class,
        Lock::class,
        // Event
        JobCompleted::class,
        JobFailed::class,
        UserEmailChanged::class,
        UserLoggedIn::class,
        UserPasswordChanged::class,
        UserRegistered::class,
        UserVerified::class,
        // Migration
        QueryStep::class,
        TaskStep::class,
        // Queue
        DatabaseQueue::class,
        // Repository
        Column::class,
        RepositoryQuery::class,
        // Task
        TaskArgument::class,
        TaskOption::class,
    ];

    public function _before(InstantiationTester $I)
    {
        $this->instantiateTaskRunner($this->configFiles);
    }

    // tests
    public function instantiations(InstantiationTester $I)
    {
        $sweep = new InstantiationSweep($this->get(ContainerInterface::class));

        $result = $sweep->run($this->namespaces, $this->skippedClasses);

        verify($result->describeFailures())->equals('');
        verify($result->isSuccessful())->true();
    }
}
