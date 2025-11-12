<?php

namespace Tests\Support;

use App\Actions\User\ChangeEmail;
use App\Actions\User\DeleteLinkedForm;
use App\Actions\User\Main;
use App\Actions\User\Profile;
use App\Actions\User\RegisterForm;
use App\Actions\User\ResendActivationMail;
use App\Actions\User\Subscribe;
use App\Actions\User\SubscribeSuccess;
use App\Core\FormService;
use Codeception\Test\Unit;
use WebFramework\Task\TaskRunner;

/**
 * @internal
 *
 * @covers \WebFramework\Task\TaskRunner
 */
trait TaskRunnerTrait
{
    private TaskRunner $taskRunner;
    protected function instantiateTaskRunner(array $configFiles = [])
    {
        $this->taskRunner = new TaskRunner(
            appDir: __DIR__.'/../..',
        );
        if (!empty($configFiles))
        {
            $this->taskRunner->setConfigFiles($configFiles);
        }
        $this->taskRunner->build();
    }

    protected function get(string $class)
    {
        return $this->taskRunner->get($class);
    }
}
