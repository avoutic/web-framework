<?php

namespace Tests\Instantiations;

use HaydenPierce\ClassFinder\ClassFinder;
use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\Config\ConfigBuilder;
use WebFramework\Database\DatabaseResultWrapper;
use WebFramework\Event\QueuedEventListener;
use WebFramework\Event\UserEmailChanged;
use WebFramework\Event\UserLoggedIn;
use WebFramework\Event\UserPasswordChanged;
use WebFramework\Event\UserRegistered;
use WebFramework\Event\UserVerified;
use WebFramework\Migration\QueryStep;
use WebFramework\Migration\TaskStep;
use WebFramework\Queue\DatabaseQueue;
use WebFramework\Repository\RepositoryCore;
use WebFramework\SanityCheck\SanityCheckBase;
use WebFramework\Task\ConsoleTask;
use WebFramework\Task\TaskArgument;
use WebFramework\Task\TaskOption;

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
        // Event
        QueuedEventListener::class,
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
        RepositoryCore::class,
        // SanityCheck
        SanityCheckBase::class,
        // Task
        ConsoleTask::class,
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
        ClassFinder::disablePSR4Vendors();

        $count = 0;

        foreach ($this->namespaces as $namespace)
        {
            $classes = ClassFinder::getClassesInNamespace($namespace);

            foreach ($classes as $class)
            {
                if (in_array($class, $this->skippedClasses))
                {
                    continue;
                }

                $this->get($class);
                $count++;
            }
        }

        verify($count)->equals(124);
    }
}
