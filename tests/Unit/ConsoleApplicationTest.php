<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use WebFramework\Console\ConsoleApplication;
use WebFramework\Console\ConsoleTaskRegistryService;
use WebFramework\Diagnostics\DebugService;
use WebFramework\Exception\ArgumentParserException;
use WebFramework\Logging\LogService;
use WebFramework\Task\ConsoleTask;
use WebFramework\Task\TaskRunner;

/**
 * @internal
 *
 * @covers \WebFramework\Console\ConsoleApplication
 */
final class ConsoleApplicationTest extends Unit
{
    /** @var resource */
    private $output;

    /**
     * @param array<string, mixed> $debugService
     * @param array<string, mixed> $logService
     */
    private function buildApplication(\Throwable $thrown, array $debugService, array $logService): ConsoleApplication
    {
        $this->output = fopen('php://memory', 'w+');

        return new ConsoleApplication(
            $this->makeEmpty(DebugService::class, $debugService),
            $this->makeEmpty(LogService::class, $logService),
            $this->makeEmpty(TaskRunner::class, [
                'executeTaskObject' => function () use ($thrown) {
                    throw $thrown;
                },
            ]),
            $this->makeEmpty(ConsoleTaskRegistryService::class, [
                'getTaskForCommand' => $this->makeEmpty(ConsoleTask::class),
            ]),
            false,
            $this->output,
        );
    }

    private function getOutput(): string
    {
        rewind($this->output);

        return (string) stream_get_contents($this->output);
    }

    public function testFailingTaskIsLoggedAndReported()
    {
        $exception = new \RuntimeException('nightly job failed');
        $channels = [];

        $application = $this->buildApplication(
            $exception,
            [
                'reportException' => Expected::once(function ($reported) use ($exception) {
                    verify($reported)->same($exception);
                }),
            ],
            [
                'error' => Expected::exactly(2, function ($channel, $message) use (&$channels) {
                    $channels[] = $channel;
                    verify($message)->stringContainsString('nightly job failed');
                    verify($message)->stringContainsString('cron:nightly');
                }),
            ],
        );

        verify($application->run(['framework', 'cron:nightly']))->same(1);
        verify($channels)->same(['default', 'exception']);
        verify($this->getOutput())->stringContainsString('nightly job failed');
    }

    public function testUsageErrorIsNotReported()
    {
        $application = $this->buildApplication(
            new ArgumentParserException('Missing argument'),
            [
                'reportException' => Expected::never(),
            ],
            [
                'error' => Expected::never(),
            ],
        );

        verify($application->run(['framework', 'cron:nightly']))->same(1);
        verify($this->getOutput())->stringContainsString('Missing argument');
    }

    public function testFailureWhileLoggingDoesNotHideTheTaskError()
    {
        $application = $this->buildApplication(
            new \RuntimeException('nightly job failed'),
            [
                'reportException' => Expected::once(),
            ],
            [
                'error' => function () {
                    throw new \RuntimeException('logger down');
                },
            ],
        );

        verify($application->run(['framework', 'cron:nightly']))->same(1);
        verify($this->getOutput())->stringContainsString('nightly job failed');
    }
}
