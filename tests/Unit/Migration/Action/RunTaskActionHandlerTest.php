<?php

namespace Tests\Unit\Migration\Action;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use WebFramework\Migration\Action\RunTaskActionHandler;
use WebFramework\Migration\TaskStep;
use WebFramework\Task\Task;

/**
 * @internal
 *
 * @covers \WebFramework\Migration\Action\AbstractActionHandler
 * @covers \WebFramework\Migration\Action\RunTaskActionHandler
 */
final class RunTaskActionHandlerTest extends Unit
{
    public function testGetType()
    {
        $container = $this->makeEmpty(Container::class);
        $handler = new RunTaskActionHandler($container);
        verify($handler->getType())->equals('run_task');
    }

    public function testBuildStepWithValidAction()
    {
        $task = $this->makeEmpty(Task::class);
        $container = $this->makeEmpty(Container::class, [
            'get' => Expected::once($task),
        ]);

        $handler = new RunTaskActionHandler($container);
        $action = [
            'task' => 'App\Task\TestTask',
        ];

        $step = $handler->buildStep($action);
        verify($step)->instanceOf(TaskStep::class);
        verify($step->getTask())->equals($task);
    }

    public function testBuildStepThrowsExceptionWhenTaskMissing()
    {
        $container = $this->makeEmpty(Container::class);
        $handler = new RunTaskActionHandler($container);
        $action = [];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No task specified');
    }

    public function testBuildStepThrowsExceptionWhenTaskNotString()
    {
        $container = $this->makeEmpty(Container::class);
        $handler = new RunTaskActionHandler($container);
        $action = [
            'task' => 123,
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\InvalidArgumentException::class, 'No task specified');
    }

    public function testBuildStepThrowsExceptionWhenTaskDoesNotImplementTask()
    {
        $notATask = new \stdClass();
        $container = $this->makeEmpty(Container::class, [
            'get' => Expected::once($notATask),
        ]);

        $handler = new RunTaskActionHandler($container);
        $action = [
            'task' => 'App\Task\NotATask',
        ];

        verify(function () use ($handler, $action) {
            $handler->buildStep($action);
        })->callableThrows(\RuntimeException::class, 'Task App\Task\NotATask does not implement Task');
    }
}
