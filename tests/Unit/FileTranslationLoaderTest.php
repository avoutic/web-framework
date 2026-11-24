<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use WebFramework\Cache\Cache;
use WebFramework\Core\RuntimeEnvironment;
use WebFramework\Translation\FileTranslationLoader;

/**
 * @internal
 *
 * @covers \WebFramework\Translation\FileTranslationLoader
 */
final class FileTranslationLoaderTest extends Unit
{
    /**
     * @param array<string, mixed> $cache
     */
    private function buildLoader(array $cache): FileTranslationLoader
    {
        // The translations of the framework itself
        return new FileTranslationLoader(
            $this->makeEmpty(Cache::class, $cache),
            $this->makeEmpty(RuntimeEnvironment::class, ['getAppDir' => dirname(__DIR__, 2)]),
            ['translations'],
        );
    }

    public function testCachedTranslationsAreUsed()
    {
        $loader = $this->buildLoader([
            'get' => Expected::once(['login' => ['success' => 'From the cache']]),
            'set' => Expected::never(),
        ]);

        verify($loader->loadTranslation('en', 'login', 'success'))->equals('From the cache');
    }

    public function testEntryThatExpiresBetweenExistsAndGetFallsBackToDisk()
    {
        // The situation of the race: the entry is there when asked, and gone when read
        $loader = $this->buildLoader([
            'exists' => true,
            'get' => false,
            'set' => Expected::once(function ($path, $value, $expiresAfter) {
                verify($value)->isArray();
                verify($expiresAfter)->equals(10);
            }),
        ]);

        verify($loader->loadTranslation('en', 'login', 'success'))->equals('Login successful');
    }

    public function testCacheEntryThatIsNotAnArrayIsIgnored()
    {
        $loader = $this->buildLoader([
            'exists' => true,
            'get' => 'corrupt',
            'set' => Expected::once(),
        ]);

        verify($loader->loadTranslation('en', 'login', 'success'))->equals('Login successful');
    }
}
