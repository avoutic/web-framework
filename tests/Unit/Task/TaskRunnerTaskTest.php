<?php

namespace Tests\Unit\Task;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use WebFramework\Exception\ArgumentParserException;
use WebFramework\Task\Task;
use WebFramework\Task\TaskRunner;
use WebFramework\Task\TaskRunnerTask;

/**
 * @internal
 *
 * @covers \WebFramework\Task\TaskRunnerTask
 */
final class TaskRunnerTaskTest extends Unit
{
    public function testGetCommand()
    {
        $taskRunner = $this->makeEmpty(TaskRunner::class);
        $task = new TaskRunnerTask($taskRunner);

        verify($task->getCommand())->equals('task:run');
    }

    public function testSetMaxAttemptsWithValidValue()
    {
        $taskRunner = $this->makeEmpty(TaskRunner::class);
        $task = new TaskRunnerTask($taskRunner);

        $task->setMaxAttempts('3');

        // Use reflection to verify private property
        $reflection = new \ReflectionClass($task);
        $property = $reflection->getProperty('maxAttempts');
        $property->setAccessible(true);

        verify($property->getValue($task))->equals(3);
    }

    public function testSetMaxAttemptsThrowsExceptionWhenNotNumeric()
    {
        $taskRunner = $this->makeEmpty(TaskRunner::class);
        $task = new TaskRunnerTask($taskRunner);

        verify(function () use ($task) {
            $task->setMaxAttempts('invalid');
        })->callableThrows(ArgumentParserException::class, 'Attempts must be a positive number');
    }

    public function testSetMaxAttemptsThrowsExceptionWhenLessThanOne()
    {
        $taskRunner = $this->makeEmpty(TaskRunner::class);
        $task = new TaskRunnerTask($taskRunner);

        verify(function () use ($task) {
            $task->setMaxAttempts('0');
        })->callableThrows(ArgumentParserException::class, 'Attempts must be a positive number');
    }

    public function testSetBackoffWithValidValue()
    {
        $taskRunner = $this->makeEmpty(TaskRunner::class);
        $task = new TaskRunnerTask($taskRunner);

        $task->setBackoff('5');

        // Use reflection to verify private property
        $reflection = new \ReflectionClass($task);
        $property = $reflection->getProperty('backoffInSecs');
        $property->setAccessible(true);

        verify($property->getValue($task))->equals(5);
    }

    public function testSetBackoffThrowsExceptionWhenNotNumeric()
    {
        $taskRunner = $this->makeEmpty(TaskRunner::class);
        $task = new TaskRunnerTask($taskRunner);

        verify(function () use ($task) {
            $task->setBackoff('invalid');
        })->callableThrows(ArgumentParserException::class, 'Backoff must be a non-negative number');
    }

    public function testSetBackoffThrowsExceptionWhenNegative()
    {
        $taskRunner = $this->makeEmpty(TaskRunner::class);
        $task = new TaskRunnerTask($taskRunner);

        verify(function () use ($task) {
            $task->setBackoff('-1');
        })->callableThrows(ArgumentParserException::class, 'Backoff must be a non-negative number');
    }

    public function testExecuteWithRetrySucceedsOnFirstAttempt()
    {
        $mockTask = $this->makeEmpty(Task::class, [
            'execute' => Expected::once(),
        ]);

        $taskRunner = $this->makeEmpty(TaskRunner::class);
        $taskRunnerTask = new TaskRunnerTask($taskRunner);

        // Use reflection to test the private executeWithRetry method
        $reflection = new \ReflectionClass($taskRunnerTask);
        $method = $reflection->getMethod('executeWithRetry');
        $method->setAccessible(true);

        $method->invoke($taskRunnerTask, $mockTask);
    }

    public function testExecuteWithRetryRetriesOnFailure()
    {
        $exception = new \RuntimeException('Task failed');
        $callCount = 0;
        $mockTask = $this->makeEmpty(Task::class, [
            'execute' => Expected::exactly(2, function () use ($exception, &$callCount) {
                $callCount++;
                if ($callCount === 1)
                {
                    throw $exception;
                }
            }),
        ]);

        $taskRunner = $this->makeEmpty(TaskRunner::class);
        $taskRunnerTask = new TaskRunnerTask($taskRunner);

        // Use reflection to set maxAttempts
        $reflection = new \ReflectionClass($taskRunnerTask);
        $maxAttemptsProperty = $reflection->getProperty('maxAttempts');
        $maxAttemptsProperty->setAccessible(true);
        $maxAttemptsProperty->setValue($taskRunnerTask, 2);

        $method = $reflection->getMethod('executeWithRetry');
        $method->setAccessible(true);

        $method->invoke($taskRunnerTask, $mockTask);
    }

    public function testExecuteWithRetryThrowsAfterMaxAttempts()
    {
        $exception = new \RuntimeException('Task failed');
        $mockTask = $this->makeEmpty(Task::class, [
            'execute' => Expected::exactly(3, function () use ($exception) {
                throw $exception;
            }),
        ]);

        $taskRunner = $this->makeEmpty(TaskRunner::class);
        $taskRunnerTask = new TaskRunnerTask($taskRunner);

        // Use reflection to set maxAttempts
        $reflection = new \ReflectionClass($taskRunnerTask);
        $maxAttemptsProperty = $reflection->getProperty('maxAttempts');
        $maxAttemptsProperty->setAccessible(true);
        $maxAttemptsProperty->setValue($taskRunnerTask, 3);

        $method = $reflection->getMethod('executeWithRetry');
        $method->setAccessible(true);

        verify(function () use ($method, $taskRunnerTask, $mockTask) {
            $method->invoke($taskRunnerTask, $mockTask);
        })->callableThrows(\RuntimeException::class, 'Task failed');
    }
}
