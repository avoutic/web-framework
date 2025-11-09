<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Console;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Config\ConfigService;
use WebFramework\Task\CacheClearTask;
use WebFramework\Task\CleanupVerificationCodesTask;
use WebFramework\Task\ConfigShowTask;
use WebFramework\Task\ConsoleTask;
use WebFramework\Task\DbConvertFromSchemeTask;
use WebFramework\Task\DbConvertProductionTask;
use WebFramework\Task\DbMakeMigrationTask;
use WebFramework\Task\DbMigrateTask;
use WebFramework\Task\DbStatusTask;
use WebFramework\Task\DefinitionsShowTask;
use WebFramework\Task\QueueList;
use WebFramework\Task\QueueWorker;
use WebFramework\Task\SanityCheckTask;
use WebFramework\Task\TaskRunnerTask;

/**
 * Class ConsoleTaskRegistryService.
 *
 * Registers and manages app-specific console tasks.
 */
class ConsoleTaskRegistryService
{
    /** @var array<string, class-string> */
    private array $frameworkCommands = [
        'cache:clear' => CacheClearTask::class,
        'config:show' => ConfigShowTask::class,
        'definitions:show' => DefinitionsShowTask::class,
        'db:migrate' => DbMigrateTask::class,
        'db:convert-from-scheme' => DbConvertFromSchemeTask::class,
        'db:convert-production' => DbConvertProductionTask::class,
        'db:status' => DbStatusTask::class,
        'db:make' => DbMakeMigrationTask::class,
        'queue:list' => QueueList::class,
        'queue:worker' => QueueWorker::class,
        'sanity:check' => SanityCheckTask::class,
        'task:run' => TaskRunnerTask::class,
        'verification-codes:cleanup' => CleanupVerificationCodesTask::class,
    ];

    /**
     * ConsoleTaskRegistryService constructor.
     *
     * @param ConfigService $configService The configuration service
     * @param Container     $container     The container
     */
    public function __construct(
        private ConfigService $configService,
        private Container $container,
    ) {}

    /**
     * Retrieve all framework-specific tasks.
     *
     * @return array<string, class-string> Array of command => task mappings
     */
    public function getFrameworkTasks(): array
    {
        return $this->frameworkCommands;
    }

    /**
     * Retrieve all app-specific tasks.
     *
     * @return array<string, class-string> Array of command => task mappings
     */
    public function getAppTasks(): array
    {
        return $this->configService->get('console_tasks');
    }

    /**
     * Get a task for a command.
     *
     * @param string $command The command to get a task for
     *
     * @return ConsoleTask The task for the command
     */
    public function getTaskForCommand(string $command): ?ConsoleTask
    {
        $taskClass = $this->frameworkCommands[$command] ?? null;

        if (!$taskClass)
        {
            $taskClass = $this->getAppTasks()[$command] ?? null;
        }

        if (!$taskClass)
        {
            return null;
        }

        $task = $this->container->get($taskClass);

        if (!$task instanceof ConsoleTask)
        {
            throw new \RuntimeException("Task {$taskClass} does not implement ConsoleTask");
        }

        return $task;
    }
}
