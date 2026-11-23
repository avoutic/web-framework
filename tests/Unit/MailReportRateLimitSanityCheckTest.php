<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WebFramework\Cache\Cache;
use WebFramework\Cache\NullCache;
use WebFramework\Diagnostics\MailReportFunction;
use WebFramework\Diagnostics\NullReportFunction;
use WebFramework\SanityCheck\MailReportRateLimit;

/**
 * @internal
 *
 * @covers \WebFramework\SanityCheck\MailReportRateLimit
 */
final class MailReportRateLimitSanityCheckTest extends Unit
{
    private function storingCache(): Cache
    {
        $store = [];

        return $this->makeEmpty(Cache::class, [
            'get' => function ($path) use (&$store) {
                return $store[$path] ?? false;
            },
            'set' => function ($path, $value) use (&$store) {
                $store[$path] = $value;
            },
            'invalidate' => Expected::once(function ($path) use (&$store) {
                unset($store[$path]);
            }),
        ]);
    }

    public function testPassesWithoutMailReports()
    {
        $check = new MailReportRateLimit(
            $this->makeEmpty(Cache::class, [
                'get' => Expected::never(),
                'set' => Expected::never(),
            ]),
            $this->makeEmpty(LoggerInterface::class),
            new NullReportFunction(),
        );

        verify($check->performChecks())->true();
    }

    public function testPassesWithMailReportsAndStoringCache()
    {
        $check = new MailReportRateLimit(
            $this->storingCache(),
            $this->makeEmpty(LoggerInterface::class, [
                'emergency' => Expected::never(),
            ]),
            $this->makeEmpty(MailReportFunction::class),
        );

        verify($check->performChecks())->true();
    }

    public function testFailsWithMailReportsAndCacheThatDoesNotStore()
    {
        $check = new MailReportRateLimit(
            new NullCache(),
            $this->makeEmpty(LoggerInterface::class, [
                'emergency' => Expected::once(),
            ]),
            $this->makeEmpty(MailReportFunction::class),
        );

        verify($check->performChecks())->false();
    }
}
