<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WebFramework\Cache\Cache;
use WebFramework\Cache\MemoryCache;
use WebFramework\Diagnostics\MailReportFunction;
use WebFramework\SanityCheck\MailReportRateLimit;

/**
 * @internal
 *
 * @covers \WebFramework\Cache\MemoryCache
 * @covers \WebFramework\SanityCheck\MailReportRateLimit
 */
final class MemoryCacheTest extends Unit
{
    protected function _before(): void
    {
        Carbon::setTestNow('2025-01-01 12:00:00');
    }

    protected function _after(): void
    {
        Carbon::setTestNow();
    }

    public function testStoresAndReturnsValues()
    {
        $cache = new MemoryCache();

        verify($cache)->instanceOf(Cache::class);
        verify($cache->exists('key'))->false();
        verify($cache->get('key'))->false();

        $cache->set('key', ['a' => 1]);

        verify($cache->exists('key'))->true();
        verify($cache->get('key'))->same(['a' => 1]);
    }

    public function testFalsyValuesAreStored()
    {
        $cache = new MemoryCache();

        $cache->set('zero', 0);
        $cache->set('empty', '');
        $cache->set('null', null);

        verify($cache->exists('zero'))->true();
        verify($cache->get('zero'))->same(0);
        verify($cache->get('empty'))->same('');
        verify($cache->exists('null'))->true();
        verify($cache->get('null'))->null();
    }

    public function testValueExpiresAfterItsLifetime()
    {
        $cache = new MemoryCache();

        $cache->set('key', 'value', 60);
        $cache->set('forever', 'value');

        Carbon::setTestNow(Carbon::now()->addSeconds(59));
        verify($cache->get('key'))->same('value');

        Carbon::setTestNow(Carbon::now()->addSeconds(1));
        verify($cache->exists('key'))->false();
        verify($cache->get('key'))->false();
        verify($cache->get('forever'))->same('value');
    }

    public function testInvalidate()
    {
        $cache = new MemoryCache();

        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->invalidate('a');
        $cache->invalidate('unknown');

        verify($cache->exists('a'))->false();
        verify($cache->get('b'))->same(2);
    }

    public function testInvalidateTags()
    {
        $cache = new MemoryCache();

        $cache->setWithTags('user-1', 'u1', ['users', 'user-1']);
        $cache->setWithTags('user-2', 'u2', ['users']);
        $cache->setWithTags('post-1', 'p1', ['posts']);
        $cache->set('untagged', 'x');

        $cache->invalidateTags(['user-1']);
        verify($cache->exists('user-1'))->false();
        verify($cache->exists('user-2'))->true();

        $cache->invalidateTags(['users', 'unknown']);
        verify($cache->exists('user-2'))->false();
        verify($cache->get('post-1'))->same('p1');
        verify($cache->get('untagged'))->same('x');
    }

    public function testSettingAgainReplacesValueTagsAndLifetime()
    {
        $cache = new MemoryCache();

        $cache->setWithTags('key', 'old', ['old-tag'], 10);
        $cache->set('key', 'new');

        $cache->invalidateTags(['old-tag']);
        Carbon::setTestNow(Carbon::now()->addSeconds(100));

        verify($cache->get('key'))->same('new');
    }

    public function testFlush()
    {
        $cache = new MemoryCache();

        $cache->set('a', 1);
        $cache->setWithTags('b', 2, ['tag']);
        $cache->flush();

        verify($cache->exists('a'))->false();
        verify($cache->exists('b'))->false();
    }

    public function testMailRateLimitCheckDoesNotAcceptAMemoryCache()
    {
        // It stores, but only within one process, so it does not limit anything across requests
        $check = new MailReportRateLimit(
            new MemoryCache(),
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(MailReportFunction::class),
        );

        verify($check->performChecks())->false();
    }
}
