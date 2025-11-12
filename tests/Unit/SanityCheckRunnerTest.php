<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
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
}
