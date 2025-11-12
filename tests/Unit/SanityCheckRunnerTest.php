<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Container\ContainerInterface;
use WebFramework\Diagnostics\Instrumentation;
use WebFramework\Exception\SanityCheckException;
use WebFramework\SanityCheck\SanityCheckModule;
use WebFramework\SanityCheck\SanityCheckRunner;
use WebFramework\Support\StoredValuesService;

/**
 * @internal
 *
 * @covers \WebFramework\SanityCheck\SanityCheckRunner
 */
final class SanityCheckRunnerTest extends Unit
{
    public function testNeedsRunInDevModeWithNullCommit()
    {
        // Test when commit is null (dev mode) and last check was less than 5 seconds ago
        $recentTimestamp = Carbon::now()->subSeconds(10)->timestamp;

        // Test when commit is null (dev mode) and last check was more than 5 seconds ago
        $storedValuesService = $this->makeEmpty(
            StoredValuesService::class,
            [
                'getValue' => function ($key, $default) use ($recentTimestamp) {
                    if ($key === 'sanity_check.last_check')
                    {
                        return (string) $recentTimestamp;
                    }

                    return $default;
                },
                'setValue' => Expected::once(),
            ]
        );

        $runner = $this->make(
            SanityCheckRunner::class,
            [
                'storedValuesService' => $storedValuesService,
            ]
        );

        $result = $runner->needsRun(null);

        verify($result)->equals(true);
    }

    public function testNeedsRunInDevModeRecentCheck()
    {
        // Test when commit is null (dev mode) and last check was less than 5 seconds ago
        $recentTimestamp = Carbon::now()->subSeconds(2)->timestamp;

        $storedValuesService = $this->makeEmpty(
            StoredValuesService::class,
            [
                'getValue' => function ($key, $default) use ($recentTimestamp) {
                    if ($key === 'sanity_check.last_check')
                    {
                        return (string) $recentTimestamp;
                    }

                    return $default;
                },
                'setValue' => Expected::never(),
            ]
        );

        $runner = $this->make(
            SanityCheckRunner::class,
            [
                'storedValuesService' => $storedValuesService,
            ]
        );

        $result = $runner->needsRun(null);

        verify($result)->equals(false); // Should return false due to throttling
    }

    public function testNeedsRunWithForceRun()
    {
        $runner = $this->make(SanityCheckRunner::class);
        $runner->forceRun();

        $result = $runner->needsRun('some-commit');

        verify($result)->equals(true);
    }

    public function testNeedsRunWithCommitNotChecked()
    {
        $storedValuesService = $this->makeEmpty(
            StoredValuesService::class,
            [
                'getValue' => function ($key, $default) {
                    if ($key === 'sanity_check.checked_abc123')
                    {
                        return '0';
                    }

                    return $default;
                },
            ]
        );

        $runner = $this->make(
            SanityCheckRunner::class,
            [
                'storedValuesService' => $storedValuesService,
            ]
        );

        $result = $runner->needsRun('abc123');

        verify($result)->equals(true);
    }

    public function testNeedsRunWithCommitAlreadyChecked()
    {
        $storedValuesService = $this->makeEmpty(
            StoredValuesService::class,
            [
                'getValue' => function ($key, $default) {
                    if ($key === 'sanity_check.checked_abc123')
                    {
                        return '1';
                    }

                    return $default;
                },
            ]
        );

        $runner = $this->make(
            SanityCheckRunner::class,
            [
                'storedValuesService' => $storedValuesService,
            ]
        );

        $result = $runner->needsRun('abc123');

        verify($result)->equals(false);
    }

    public function testExecuteWithNoModules()
    {
        $runner = $this->make(SanityCheckRunner::class);

        $result = $runner->execute();

        verify($result)->equals(true);
    }

    public function testExecuteWhenCommitAlreadyChecked()
    {
        $storedValuesService = $this->makeEmpty(
            StoredValuesService::class,
            [
                'getValue' => function ($key, $default) {
                    if ($key === 'sanity_check.checked_abc123')
                    {
                        return '1'; // Already checked
                    }

                    return $default;
                },
            ]
        );

        $runner = $this->make(
            SanityCheckRunner::class,
            [
                'storedValuesService' => $storedValuesService,
                'buildInfo' => ['commit' => 'abc123'],
            ]
        );
        $runner->add('TestModule', []);

        $result = $runner->execute();

        verify($result)->equals(true);
    }

    public function testExecuteWithSuccessfulModule()
    {
        $module = $this->makeEmpty(
            SanityCheckModule::class,
            [
                'setConfig' => Expected::once(),
                'performChecks' => Expected::once(function () {
                    return true;
                }),
            ]
        );

        $container = $this->makeEmpty(
            ContainerInterface::class,
            [
                'get' => Expected::once(function () use ($module) {
                    return $module;
                }),
            ]
        );

        $instrumentation = $this->makeEmpty(
            Instrumentation::class,
            [
                'startSpan' => Expected::once(function () {
                    return 'span';
                }),
                'finishSpan' => Expected::once(),
            ]
        );

        $storedValuesService = $this->makeEmpty(
            StoredValuesService::class,
            [
                'getValue' => function ($key, $default) {
                    if ($key === 'sanity_check.checked_abc123')
                    {
                        return '0';
                    }

                    return $default;
                },
                'setValue' => Expected::once(),
            ]
        );

        $runner = $this->make(
            SanityCheckRunner::class,
            [
                'container' => $container,
                'instrumentation' => $instrumentation,
                'storedValuesService' => $storedValuesService,
                'buildInfo' => ['commit' => 'abc123'],
            ]
        );
        $runner->add('TestModule', ['config' => 'value']);

        $result = $runner->execute();

        verify($result)->equals(true);
    }

    public function testExecuteWithFailingModule()
    {
        $module = $this->makeEmpty(
            SanityCheckModule::class,
            [
                'setConfig' => Expected::once(),
                'performChecks' => Expected::once(function () {
                    return false;
                }),
            ]
        );

        $container = $this->makeEmpty(
            ContainerInterface::class,
            [
                'get' => Expected::once(function () use ($module) {
                    return $module;
                }),
            ]
        );

        $instrumentation = $this->makeEmpty(
            Instrumentation::class,
            [
                'startSpan' => Expected::once(function () {
                    return 'span';
                }),
                'finishSpan' => Expected::once(),
            ]
        );

        $storedValuesService = $this->makeEmpty(
            StoredValuesService::class,
            [
                'getValue' => function ($key, $default) {
                    if ($key === 'sanity_check.checked_abc123')
                    {
                        return '0';
                    }

                    return $default;
                },
            ]
        );

        $runner = $this->make(
            SanityCheckRunner::class,
            [
                'container' => $container,
                'instrumentation' => $instrumentation,
                'storedValuesService' => $storedValuesService,
                'buildInfo' => ['commit' => 'abc123'],
            ]
        );
        $runner->add('TestModule', []);

        verify(function () use ($runner) {
            $runner->execute();
        })->callableThrows(SanityCheckException::class, 'Sanity check failed');
    }

    public function testExecuteWithNonSanityCheckModule()
    {
        $notAModule = new \stdClass();

        $container = $this->makeEmpty(
            ContainerInterface::class,
            [
                'get' => Expected::once(function () use ($notAModule) {
                    return $notAModule;
                }),
            ]
        );

        $storedValuesService = $this->makeEmpty(
            StoredValuesService::class,
            [
                'getValue' => function ($key, $default) {
                    if ($key === 'sanity_check.checked_abc123')
                    {
                        return '0';
                    }

                    return $default;
                },
            ]
        );

        $runner = $this->make(
            SanityCheckRunner::class,
            [
                'container' => $container,
                'storedValuesService' => $storedValuesService,
                'buildInfo' => ['commit' => 'abc123'],
            ]
        );
        $runner->add('NotAModule', []);

        verify(function () use ($runner) {
            $runner->execute();
        })->callableThrows(\RuntimeException::class, "Class 'NotAModule' in not a SanityCheckModule");
    }

    public function testExecuteWithFixingEnabled()
    {
        $module = $this->makeEmpty(
            SanityCheckModule::class,
            [
                'setConfig' => Expected::once(),
                'allowFixing' => Expected::once(),
                'performChecks' => Expected::once(function () {
                    return true;
                }),
            ]
        );

        $container = $this->makeEmpty(
            ContainerInterface::class,
            [
                'get' => Expected::once(function () use ($module) {
                    return $module;
                }),
            ]
        );

        $instrumentation = $this->makeEmpty(
            Instrumentation::class,
            [
                'startSpan' => Expected::once(function () {
                    return 'span';
                }),
                'finishSpan' => Expected::once(),
            ]
        );

        $storedValuesService = $this->makeEmpty(
            StoredValuesService::class,
            [
                'getValue' => function ($key, $default) {
                    if ($key === 'sanity_check.checked_abc123')
                    {
                        return '0';
                    }

                    return $default;
                },
                'setValue' => Expected::once(),
            ]
        );

        $runner = $this->make(
            SanityCheckRunner::class,
            [
                'container' => $container,
                'instrumentation' => $instrumentation,
                'storedValuesService' => $storedValuesService,
                'buildInfo' => ['commit' => 'abc123'],
            ]
        );
        $runner->allowFixing();
        $runner->add('TestModule', []);

        $result = $runner->execute();

        verify($result)->equals(true);
    }

    public function testExecuteWithVerboseEnabled()
    {
        $module = $this->makeEmpty(
            SanityCheckModule::class,
            [
                'setConfig' => Expected::once(),
                'setVerbose' => Expected::once(),
                'performChecks' => Expected::once(function () {
                    return true;
                }),
            ]
        );

        $container = $this->makeEmpty(
            ContainerInterface::class,
            [
                'get' => Expected::once(function () use ($module) {
                    return $module;
                }),
            ]
        );

        $instrumentation = $this->makeEmpty(
            Instrumentation::class,
            [
                'startSpan' => Expected::once(function () {
                    return 'span';
                }),
                'finishSpan' => Expected::once(),
            ]
        );

        $storedValuesService = $this->makeEmpty(
            StoredValuesService::class,
            [
                'getValue' => function ($key, $default) {
                    if ($key === 'sanity_check.checked_abc123')
                    {
                        return '0';
                    }

                    return $default;
                },
                'setValue' => Expected::once(),
            ]
        );

        $runner = $this->make(
            SanityCheckRunner::class,
            [
                'container' => $container,
                'instrumentation' => $instrumentation,
                'storedValuesService' => $storedValuesService,
                'buildInfo' => ['commit' => 'abc123'],
            ]
        );
        $runner->setVerbose();
        $runner->add('TestModule', []);

        $result = $runner->execute();

        verify($result)->equals(true);
    }

    public function testExecuteRegistersRunWithCommit()
    {
        $module = $this->makeEmpty(
            SanityCheckModule::class,
            [
                'setConfig' => Expected::once(),
                'performChecks' => Expected::once(function () {
                    return true;
                }),
            ]
        );

        $container = $this->makeEmpty(
            ContainerInterface::class,
            [
                'get' => Expected::once(function () use ($module) {
                    return $module;
                }),
            ]
        );

        $instrumentation = $this->makeEmpty(
            Instrumentation::class,
            [
                'startSpan' => Expected::once(function () {
                    return 'span';
                }),
                'finishSpan' => Expected::once(),
            ]
        );

        $storedValuesService = $this->makeEmpty(
            StoredValuesService::class,
            [
                'getValue' => function ($key, $default) {
                    if ($key === 'sanity_check.checked_abc123')
                    {
                        return '0';
                    }

                    return $default;
                },
                'setValue' => Expected::once(), // Once for registerRun
            ]
        );

        $runner = $this->make(
            SanityCheckRunner::class,
            [
                'container' => $container,
                'instrumentation' => $instrumentation,
                'storedValuesService' => $storedValuesService,
                'buildInfo' => ['commit' => 'abc123'],
            ]
        );
        $runner->add('TestModule', []);

        $result = $runner->execute();

        verify($result)->equals(true);
    }
}
